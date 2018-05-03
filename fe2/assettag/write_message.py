from __future__ import division
import pymssql
from get_paying_hours import paying_hours
from collections import defaultdict

def write_message(Type, Target, From, To, WorkingDay, WorkingHour, URL, Shift):
	html = ""
	Sum = 0
	Sum_Fail = 0
	Failure_Rate = 0
	# Get PCT data in 12 hours from 112 DB
	conn = pymssql.connect("16.187.224.112", "sa", "support", "FE2CheckPoint")
	cursor = conn.cursor(as_dict=True)
	cursor.execute("""
	SELECT
		SO,
		PLO,
		Product,
		CASE
			WHEN ProductFamily.ConfigType IN ('CTO', 'BTO&CTO') AND FEFlag = 0 THEN 'CTO' 
			WHEN ProductFamily.ConfigType IN ('PPS Option', 'PPS Option 3F') THEN 'PPS Option'
			WHEN FEFlag = 1 THEN 'Complex CTO'
			WHEN ProductFamily.ConfigType IN ('ConfigRack') THEN 'Rack'
			ELSE 'Others' 
		END as ProdCat,
		{From},
		{To},
		FEFlag,
		MaxPCT AS Target
	FROM
		PCTMaster
		LEFT JOIN
		ProductFamily
		ON PCTMaster.Family=ProductFamily.ProductFamily AND (PCTMaster.ConfigType=ProductFamily.ConfigType OR PCTMaster.ConfigType is NULL)
	WHERE
		{From} is NOT NULL
		AND
		{To} >= DATEADD(hh, -12, DATEADD(hh,DATEDIFF(hh,'19000101',GETDATE()),'19000101')) 
		AND
		{To} < DATEADD(hh,DATEDIFF(hh,'19000101',GETDATE()),'19000101')
	""".format(From = From, To = To))

	if cursor.rowcount != 0:
		PCT = {}
		if (Type != 'PC'):
			SKU = []
			for row in cursor:
				if (row['ProdCat'] != 'PPS Option'):
					PCT.update({row['PLO']:{'SKU':row['Product'].split(' ')[0], From:row[From], To:row[To]}})
					SKU.append(row['Product'].split(' ')[0])	

			SKUs = "'" + "','".join(SKU) + "'"
		else:
			for row in cursor:
				PCT.update({row['PLO']:{'ProdCat':row['ProdCat'], From:row[From], To:row[To]}})
					
		conn.close()

		if (Type != 'PC'):
			# Retrieve SKU and Platform info from 111 DB
			conn = pymssql.connect("16.187.224.111", "yufei", "yufei123", "PLOdb")
			cursor = conn.cursor(as_dict=True)
			cursor.execute("""
			SELECT
				SKU,
				Name
			FROM
				SKUList
			WHERE
				SKU IN ({SKUs})
			""".format(SKUs = SKUs))

			Platform = defaultdict(lambda: 'N/A')
			for row in cursor:
				Platform[row['SKU']] = row['Name']

			conn.close()
		
		# Calculate fail rate
		# Count_Total = defaultdict(lambda: 0)
		Count = defaultdict(lambda: defaultdict(lambda: 0))
		FailedPLO = defaultdict(lambda: [])
		# Sum = 0
		# Sum_Fail = 0
		for k, v in PCT.items():
			Item = (Platform[v['SKU']] if Platform[v['SKU']] != 'N/A' else v['SKU']) if Type != 'PC' else v['ProdCat']
			Count[Item]['Total'] += 1
			Sum += 1
			Goal = Target if Type != 'PC' else Target[v['ProdCat']]
			if (paying_hours(v[From], v[To], WorkingDay, WorkingHour) > Goal):
				Count[Item]['Fail'] += 1
				FailedPLO[Item].append(k)
				Sum_Fail += 1
		if Sum != 0:
			Failure_Rate = Sum_Fail/Sum
		
		FailedPLOs = defaultdict(lambda: 'N/A')
		# Do sorting and stringlize
		for k, v in Count.items():
			v['Failure_Rate'] = v['Fail']/v['Total']
			FailedPLOs[k] = "'" + "','".join(FailedPLO[k]) + "'"
			
		PF = [ k for k in Count.keys() ]
		
		Sorted_PF = sorted(PF, key=lambda x: (Count[x]['Failure_Rate'], Count[x]['Fail']), reverse=True)
		
		# Generate table
		if (Type == 'MR'):
			html += """
			<table border="1" width="888">
				<tr bgcolor="#1F77B4">
					<th colspan="4">备料(MR) TAT Performance</th>
				</tr>
				<tr bgcolor="#1F77B4">
					<th width="30%">Platform</th>
					<th width="20%">MR PLO QTY</th>
					<th width="30%">MR TAT Fail PLO QTY (Over 24H)</th>
					<th width="20%">Failure Rate</th>
				</tr>
			"""
		if (Type == 'P'):
			html += """
			<table border="1" width="888">
				<tr bgcolor="#FF7F0E">
					<th colspan="4">生产(P) TAT Performance</th>
				</tr>
				<tr bgcolor="#FF7F0E">
					<th width="30%">Platform</th>
					<th width="20%">Handover PLO QTY</th>
					<th width="30%">P TAT Fail PLO QTY (Over 42H)</th>
					<th width="20%">Failure Rate</th>
				</tr>
			"""
		if (Type == 'PGI'):
			html += """
			<table border="1" width="888">
				<tr bgcolor="#2CA02C">
					<th colspan="4">出货(PGI) TAT Performance</th>
				</tr>
				<tr bgcolor="#2CA02C">
					<th width="30%">Platform</th>
					<th width="20%">PGI PLO QTY</th>
					<th width="30%">PGI TAT Fail PLO QTY (Over 6H)</th>
					<th width="20%">Failure Rate</th>
				</tr>
			"""
		if (Type == 'PC'):
			html += """
			<table border="1" width="888">
				<tr bgcolor="#D6CF27">
					<th colspan="4">Overall PCT Performance</th>
				</tr>
				<tr bgcolor="#D6CF27">
					<th width="30%">Product Category</th>
					<th width="20%">PGI DG QTY</th>
					<th width="30%">PCT Fail DG QTY</th>
					<th width="20%">Failure Rate</th>
				</tr>
			"""
		# for k, v in Count.items():
		for v in Sorted_PF:
			if Type == 'PC' and v == 'CTO':
				html += '<tr bgcolor=\'#D6CF27\'>'
			else:
				html += '<tr>'
			html += '<td>' + v + '</td>'
			html += '<td>' + str(Count[v]['Total']) + '</td>'
			if (Count[v]['Fail'] > 0):	
				html += '<td bgcolor=\'#FFC7CE\'><a href=' + URL + '?PLO=' + FailedPLOs[v] + '>' + str(Count[v]['Fail']) + '</a></td>'
			else:
				html += '<td>' + str(Count[v]['Fail']) + '</td>'
			html += '<td>' + "{:.0%}".format(Count[v]['Failure_Rate']) + '</td>'
			html += '</tr>'
			
		if (Type == 'MR'):
			html += '<tr bgcolor=\'#1F77B4\'>'
		elif (Type == 'P'):
			html += '<tr bgcolor=\'#FF7F0E\'>'
		elif (Type == 'PGI'):
			html += '<tr bgcolor=\'#2CA02C\'>'
		else:
			html += "<tr>"
			
		html += """
				<th>Total</th>
				<th>{Sum}</th>
				<th>{Sum_Fail}</th>
				<th>{Failure_Rate}</th>
			</tr>
		</table>
		""".format(Sum = Sum, Sum_Fail = Sum_Fail, Failure_Rate = "{:.0%}".format(Failure_Rate))

		# Record FailRate for plot
		if (Type == 'PC'):
			Type = 'CTO'
			Sum = Count[Type]['Total']
			Sum_Fail = Count[Type]['Fail']
			if Sum != 0:
				Failure_Rate = Sum_Fail/Sum
	
	if (Type == 'PC'):
		Type = 'CTO'
	# Save failure rate data to 112 DB
	conn = pymssql.connect("16.187.224.112", "sa", "support", "FE2CheckPoint")
	cursor = conn.cursor(as_dict=True)
	cursor.execute("""
		IF OBJECT_ID('dbo.PCTFailRate', 'U') IS NULL
			CREATE TABLE PCTFailRate
			(
			Name NVARCHAR(20) NULL,
			Value NVARCHAR(20) NULL,
			Shift NVARCHAR(20) NULL
			)
	""")
	cursor.execute(
		"IF NOT EXISTS (SELECT * FROM PCTFailRate WHERE Shift = '{Shift}' AND Name ='{Type}') INSERT INTO PCTFailRate VALUES (%s, %d, %s)".format(Shift = Shift, Type = Type),
		(Type, Sum, Shift)
	)
	cursor.execute(
		"IF NOT EXISTS (SELECT * FROM PCTFailRate WHERE Shift = '{Shift}' AND Name ='{Type}_Fail') INSERT INTO PCTFailRate VALUES (%s, %d, %s)".format(Shift = Shift, Type = Type),
		 (Type + '_Fail', Sum_Fail, Shift)
	)
	cursor.execute(
		"IF NOT EXISTS (SELECT * FROM PCTFailRate WHERE Shift = '{Shift}' AND Name ='{Type}_FailRate') INSERT INTO PCTFailRate VALUES (%s, %d, %s)".format(Shift = Shift, Type = Type),
		 (Type + '_FailRate', Failure_Rate, Shift)
	)
	# cursor.executemany(
		# " INSERT INTO PCTFailRate VALUES (%s, %d, %s)".format(Shift = Shift, Type = Type),
		# [(Type, Sum, Shift),
		 # (Type + '_Fail', Sum_Fail, Shift)]
	# )
	
	conn.commit()
	conn.close()
	
	return html