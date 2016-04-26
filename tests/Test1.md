### ACL Import Bundle Test Cases

#### Test Case 1
##### Description
Show that products can be imported without category

##### Profile Configuration

Connector: Akeneo CSV Connector	| 
--------------------------------| 
Job: Product import in CSV	| 

Setting	| Value
--------|------
Allow file upload | Yes
Delimiter | ,
Allow partial import | No
Allow import products without associated with any categories | Yes
Other settings | DEFAULT

##### Actions
Execute Profile using CSV [file 1](file1.csv)

##### Expected Result
All products imported
