### ACL Import Bundle Test Cases

#### Test Case 3
##### Description
Show that products can not be imported with unknown or permissions restricted attributes

##### Profile Configuration

Connector: Akeneo CSV Connector	| 
--------------------------------| 
Job: Product import in CSV	| 

Setting	| Value
--------|------
Allow file upload | Yes
Delimiter | ,
Allow partial import | No
Allow import products without associated with any categories | No
Other settings | DEFAULT

##### Actions
* Login using "sandra" account
* Deny access to de_DE locale
* Deny access to Coats category
* Execute Profile using CSV [file 2](file2.csv)

##### Expected Result
Import stopped with error, with list of unknown attributes
