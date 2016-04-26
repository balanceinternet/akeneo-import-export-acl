### Import / Export ACL Bundle

#### Background
Akeneo have quite flexible settings to configure access level (ACL) to various entities like categories, attributes groups, locales. Frontend is covered fully with this functionality, however, import/export functionality doesn't take into account ACL configuration

As a result any user is able to export/import any data even without having access to it.

Also, a user's access to a product is based on the user's access to the categories the product is assigned to (eg. Category ACL configuration). It means that you can view/edit products only if you have access to a specific category. 

However, products not assigned to any category are accessible for every user. We are thinking that a product shouldn't be without category, as then there is not way to define the owner of the product.


#### Solution

We have implemented ACL support for Import product connector (v1)
We have added two settings (widely) to a product import profile

1. Allow import of products which are not associated with any category (the setting described above)
2. Allow import partial data
  * skip import of attributes where user does not have access
  * skip attributes which do not exist
  * skip import locale where user does not have access
  * skip import products where user does not have access to category (checking both sides, current category in PIM and category in import file)

Appropriate notifications have been added, like:

* user does not have rights to edit the product
* user does not have rights to edit attributes (list)
* category must be filled

The implemented functionality cover missing ACL part for product import

#### Roadmap
* implement the same approach for export and quick export
* implement ACL support for other import/export connectors like associations

#### Usage for Enterprise and Community versions
##### Enterprise
* All functionality above is usable for Enterprise edition

##### Community
* Community version doesn't have ACL, so the whole permissinons settings/functionality has no use
* Setting "Allow import products which not accosiated with any category" - will be definately used for the Community edition
* Setting "Allow import partial data" (skip attributes, which don't exist) - will be definately used for the Community edition as it gives possibility to use import files with unknown columns(attributes) for Akeneo, currently the user should cut off all columns which doesn't exist in Akeneo, otherwise the import will fall


#### Installation
Step 1. Use as dependency in composer
```
composer require balance/akeneo-import-export-acl
```

Step 2. Add 3 lines into ```<path_to_akeneo>/app/AppKernel.php```, into function registerBundles

```$bundles[] = new Balance\Bundle\ConnectorBundle\BalanceConnectorBundle();```

```$bundles[] = new Balance\Bundle\CatalogBundle\BalanceCatalogBundle();```

```$bundles[] = new Balance\Bundle\SecurityBundle\BalanceSecurityBundle();```

Step 3. Clear cache, execute the command: ```php console cache:clear --env=prod```

Step 4. Fix folder rights, if so: ```chmod 777 app/cache -R```

Step 5. Enjoy!



#### Test Cases
4 Test cases have been documented with Test import data.

* [Test Case 1](tests/Test1.md)
* [Test Case 2](tests/Test2.md)
* [Test Case 3](tests/Test3.md)
* [Test Case 4](tests/Test4.md)

