# VtigerApiBundle

## Installation


``` bash
$ composer require gkite13/vtiger-api-bundle
```

## Configuration


``` yaml
# config/packages/gkite13_vtiger_api.yaml
gkite13_vtiger_api:
    api:
        site_url: "http://your_crm_url"
        user: "user_name"
        access_key: "user_access_key"
```

For configure cache pool

```yaml
# config/packages/cache
pools:
# ...
  my_cache_pool:
    adapter: cache.adapter.filesystem
```
```yaml
# config/packages/gkite13_vtiger_api.yaml
gkite13_vtiger_api:
# ...
  cache:
    pool: my_cache_pool
```

## Usage

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

### Create

``` php
$lead = new \stdClass();
$lead->property1;
$lead->property2;
$result = $this->vtigerApi->create($lead);
```

### Update

``` php
$leadId = "10x12345";
$lead = $this->vtigerApi->retrieve($leadId);
$lead->property1;
$lead->property2;
$result = $this->vtigerApi->update($lead);
```

### Delete

``` php
$leadId = "10x12345";
$result = $this->vtigerApi->delete($leadId);
```
