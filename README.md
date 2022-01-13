# VtigerApiBundle
***
## Installation
***

``` bash
$ composer require gkite13/vtiger-api-bundle
```

## Configuration
***

``` yaml
gkite13_vtiger_api:
    api:
        site_url: "http://your_crm_url"
        user: "user_name"
        access_key: "user_access_key"
```

## Usage
***

### Query

``` php
$queryString = "SELECT * FROM ModuleName";
$result = $this->vtigerApi->query($queryString);
```

### Retrieve

To retrieve a record, you need a vtiger_ws_entity id and entityId. 
``` php
$leadId = "10x12345";
$result = $this->vtigerApi->retrieve($leadId);
```
