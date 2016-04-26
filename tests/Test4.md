### ACL Import Bundle Test Cases



#### Test Case 4
##### Description
* Show that products can be imported with unknown or permissions restricted attributes (including locale), only accessible attributes will be updated.
* Show that products can be updated only if user has rights to their categories

##### Profile Configuration

Connector: Akeneo CSV Connector	| 
--------------------------------| 
Job: Product import in CSV	| 

Setting	| Value
--------|------
Allow file upload | Yes
Delimiter | ,
Allow partial import | Yes
Allow import products without associated with any categories | No
Other settings | DEFAULT

##### Actions
* Loggin using "sandra" account
* Execute Profile using CSV [file 2](file2.csv)

##### Expected Result
Imported completed with a few notices regarding the imported data