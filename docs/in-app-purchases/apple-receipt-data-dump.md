```text
/app/ecommerce/src/Commands/FixSerializeErrorInAppPurchaseTables.php:126:
class stdClass#2797 (14) {
  public $id =>
  int(541)
  public $receipt =>
  string(5056) "ewoJInNpZ25hdHVyZSIgPSAiQXpONnh3Kzg0TjJVN2hxbUlvNzczWnRvK0JhSUZkZm9XSnQwNHQrV1pWM2wxMFErVlNiU04yeGhSdW43Uzl0ZXJWSGpJZ1dMVG0vUTZaMDlBc2hJN1FWRng5OXU1K0lpQzZvZDJkUzNyNzNnWWIvOTlNNHhBK3AyQlNNOXlhYkErd3dudnNRYTJRdE9HNlFQait6MHpoczl2a0hFUUt6clIzbnBYbXZRYWtubjloU1dmZUZyNFY4UmlESS84UGFtSUpybndKU2F2bFZzNXVaSU1iWVoxYXNubVpuZWlNa2Rsa256TTJ4UnorOUlkM09Hb0lIZkEwOHYxSHkwaERrZGNkcWR1L2J3eWJQeGZxaGlGZkVoWjN6N3VwTnVsM2gzbm5PUXZCcXF6cWJMbmt3SlJNdGgvYkt1c0hKVW44VVZ4RFJpMXRzSTlaUWRWNWRSZ1hzazlQVUFBQVdBTUlJRmZEQ0NCR1NnQXdJQkFn"...
  public $transaction_id =>
  NULL
  public $request_type =>
  string(12) "notification"
  public $notification_type =>
  string(7) "renewal"
  public $email =>
  NULL
  public $brand =>
  string(6) "drumeo"
  public $valid =>
  int(0)
  public $validation_error =>
  string(31) "All purchased items are expired"
  public $payment_id =>
  NULL
  public $subscription_id =>
  NULL
  public $raw_receipt_response =>
  string(11327) "O:39:"ReceiptValidator\\iTunes\\SandboxResponse":13:{s:14:"\000*\000result_code";i:0;s:12:"\000*\000bundle_id";s:23:"com.drumeo.DrumeoMobile";s:14:"\000*\000app_item_id";N;s:25:"\000*\000original_purchase_date";N;s:15:"\000*\000request_date";N;s:24:"\000*\000receipt_creation_date";N;s:10:"\000*\000receipt";a:23:{s:26:"original_purchase_date_pst";s:39:"2019-08-19 04:53:09 America/Los_Angeles";s:8:"quantity";s:1:"1";s:24:"unique_vendor_identifier";s:36:"62CE3157-A810-40F4-B862-C7E731DD6F77";s:4:"bvrs";s:7:"0."...
  public $created_at =>
  string(19) "2020-02-03 09:37:47"
  public $updated_at =>
  string(19) "2020-02-03 09:37:47"
}
/app/ecommerce/src/Commands/FixSerializeErrorInAppPurchaseTables.php:127:
class ReceiptValidator\iTunes\SandboxResponse#2236 (13) {
  protected $result_code =>
  int(0)
  protected $bundle_id =>
  string(23) "com.drumeo.DrumeoMobile"
  protected $app_item_id =>
  NULL
  protected $original_purchase_date =>
  NULL
  protected $request_date =>
  NULL
  protected $receipt_creation_date =>
  NULL
  protected $receipt =>
  array(23) {
    'original_purchase_date_pst' =>
    string(39) "2019-08-19 04:53:09 America/Los_Angeles"
    'quantity' =>
    string(1) "1"
    'unique_vendor_identifier' =>
    string(36) "62CE3157-A810-40F4-B862-C7E731DD6F77"
    'bvrs' =>
    string(7) "0.0.103"
    'expires_date_formatted' =>
    string(27) "2020-02-03 09:42:32 Etc/GMT"
    'is_in_intro_offer_period' =>
    string(5) "false"
    'purchase_date_ms' =>
    string(13) "1580722652000"
    'expires_date_formatted_pst' =>
    string(39) "2020-02-03 01:42:32 America/Los_Angeles"
    'is_trial_period' =>
    string(5) "false"
    'item_id' =>
    string(10) "1477997454"
    'unique_identifier' =>
    string(40) "bf5beb5038a9ff0387dfe939d10b61957d5a66bd"
    'original_transaction_id' =>
    string(16) "1000000558933319"
    'subscription_group_identifier' =>
    string(8) "20531400"
    'transaction_id' =>
    string(16) "1000000622190078"
    'web_order_line_item_id' =>
    string(16) "1000000049972500"
    'version_external_identifier' =>
    string(1) "0"
    'purchase_date' =>
    string(27) "2020-02-03 09:37:32 Etc/GMT"
    'product_id' =>
    string(25) "drumeo_app_monthly_member"
    'expires_date' =>
    string(13) "1580722952000"
    'original_purchase_date' =>
    string(27) "2019-08-19 11:53:09 Etc/GMT"
    'purchase_date_pst' =>
    string(39) "2020-02-03 01:37:32 America/Los_Angeles"
    'bid' =>
    string(23) "com.drumeo.DrumeoMobile"
    'original_purchase_date_ms' =>
    string(13) "1566215589000"
  }
  protected $latest_receipt =>
  NULL
  protected $latest_receipt_info =>
  array(0) {
  }
  protected $purchases =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PurchaseItem#2848 (13) {
      protected $quantity =>
      int(1)
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $web_order_line_item_id =>
      string(16) "1000000049972500"
      protected $transaction_id =>
      string(16) "1000000622190078"
      protected $original_transaction_id =>
      string(16) "1000000558933319"
      protected $purchase_date =>
      class Carbon\Carbon#2834 (3) {
        ...
      }
      protected $original_purchase_date =>
      class Carbon\Carbon#2845 (3) {
        ...
      }
      protected $expires_date =>
      class Carbon\Carbon#2841 (3) {
        ...
      }
      protected $cancellation_date =>
      NULL
      protected $is_trial_period =>
      bool(false)
      protected $is_in_intro_offer_period =>
      bool(false)
      protected $promotional_offer_id =>
      NULL
      protected $raw_data =>
      array(23) {
        ...
      }
    }
  }
  protected $pending_renewal_info =>
  array(0) {
  }
  protected $raw_data =>
  array(6) {
    'auto_renew_status' =>
    int(1)
    'status' =>
    int(0)
    'auto_renew_product_id' =>
    string(25) "drumeo_app_monthly_member"
    'receipt' =>
    array(23) {
      'original_purchase_date_pst' =>
      string(39) "2019-08-19 04:53:09 America/Los_Angeles"
      'quantity' =>
      string(1) "1"
      'unique_vendor_identifier' =>
      string(36) "62CE3157-A810-40F4-B862-C7E731DD6F77"
      'bvrs' =>
      string(7) "0.0.103"
      'expires_date_formatted' =>
      string(27) "2020-02-03 09:42:32 Etc/GMT"
      'is_in_intro_offer_period' =>
      string(5) "false"
      'purchase_date_ms' =>
      string(13) "1580722652000"
      'expires_date_formatted_pst' =>
      string(39) "2020-02-03 01:42:32 America/Los_Angeles"
      'is_trial_period' =>
      string(5) "false"
      'item_id' =>
      string(10) "1477997454"
      'unique_identifier' =>
      string(40) "bf5beb5038a9ff0387dfe939d10b61957d5a66bd"
      'original_transaction_id' =>
      string(16) "1000000558933319"
      'subscription_group_identifier' =>
      string(8) "20531400"
      'transaction_id' =>
      string(16) "1000000622190078"
      'web_order_line_item_id' =>
      string(16) "1000000049972500"
      'version_external_identifier' =>
      string(1) "0"
      'purchase_date' =>
      string(27) "2020-02-03 09:37:32 Etc/GMT"
      'product_id' =>
      string(25) "drumeo_app_monthly_member"
      'expires_date' =>
      string(13) "1580722952000"
      'original_purchase_date' =>
      string(27) "2019-08-19 11:53:09 Etc/GMT"
      'purchase_date_pst' =>
      string(39) "2020-02-03 01:37:32 America/Los_Angeles"
      'bid' =>
      string(23) "com.drumeo.DrumeoMobile"
      'original_purchase_date_ms' =>
      string(13) "1566215589000"
    }
    'latest_receipt_info' =>
    array(22) {
      'original_purchase_date_pst' =>
      string(39) "2019-08-19 04:53:09 America/Los_Angeles"
      'quantity' =>
      string(1) "1"
      'unique_vendor_identifier' =>
      string(36) "62CE3157-A810-40F4-B862-C7E731DD6F77"
      'bvrs' =>
      string(7) "0.0.103"
      'expires_date_formatted' =>
      string(27) "2020-02-03 09:42:32 Etc/GMT"
      'is_in_intro_offer_period' =>
      string(5) "false"
      'purchase_date_ms' =>
      string(13) "1580722652000"
      'expires_date_formatted_pst' =>
      string(39) "2020-02-03 01:42:32 America/Los_Angeles"
      'is_trial_period' =>
      string(5) "false"
      'item_id' =>
      string(10) "1477997454"
      'unique_identifier' =>
      string(40) "bf5beb5038a9ff0387dfe939d10b61957d5a66bd"
      'original_transaction_id' =>
      string(16) "1000000558933319"
      'subscription_group_identifier' =>
      string(8) "20531400"
      'transaction_id' =>
      string(16) "1000000622190078"
      'bid' =>
      string(23) "com.drumeo.DrumeoMobile"
      'web_order_line_item_id' =>
      string(16) "1000000049972500"
      'purchase_date' =>
      string(27) "2020-02-03 09:37:32 Etc/GMT"
      'product_id' =>
      string(25) "drumeo_app_monthly_member"
      'expires_date' =>
      string(13) "1580722952000"
      'original_purchase_date' =>
      string(27) "2019-08-19 11:53:09 Etc/GMT"
      'purchase_date_pst' =>
      string(39) "2020-02-03 01:37:32 America/Los_Angeles"
      'original_purchase_date_ms' =>
      string(13) "1566215589000"
    }
    'latest_receipt' =>
    string(4984) "ewoJInNpZ25hdHVyZSIgPSAiQXlmRHRycmlQNEJGaW92a0ZKRlJYRjhwZ2J5dlkwZzJOVithUG9FLzBxaWFNMDJ0K3JvckxRNzIyK3A5UGRtODhZamF2bzU4cHA4djQvWTdNR2t1amtTZ2lJNmpKd2lxOFpETTJTQkNXOXZtUEUveU1IclEwUmR6Sk4yQlRzM3ZaNGc3QXV4V3Bma1ErRk1WdzNHZDJuUEZKZlZGZUV5aDhQcVFEaEk3ak5CNUtCbXVObFAyTVFxeVpjU2dNZktDdWsyVEVZemszRCtLNVcwYlIrYno4TFRMaTBhZEEySjNhZU10TzNqaVBBZDN6K2pJV1JSS0NEcUh1RHY1VmNiUjhhZE9NNEJCZ1djUW9GT1BBcEgwVXk2M2xwVW9nbHU2eFo0Y2I2eW11UHZxczdML3NjQ2NtMXBTZ3BEZlYwdWdlNmdhWlJXVnN1SWxZZ1dxWjNDNEk5WUFBQVdBTUlJRmZEQ0NCR1NnQXdJQkFn"...
  }
  protected $is_retryable =>
  bool(false)
}
---------------------------------------------------------
/app/ecommerce/src/Commands/FixSerializeErrorInAppPurchaseTables.php:126:
class stdClass#2799 (14) {
  public $id =>
  int(543)
  public $receipt =>
  string(6888) "MIIUKAYJKoZIhvcNAQcCoIIUGTCCFBUCAQExCzAJBgUrDgMCGgUAMIIDyQYJKoZIhvcNAQcBoIIDugSCA7YxggOyMAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAKowDQIBDQIBAQQFAgMB1lEwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGSbmu/PEKMBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEIFNWD3hpM+DH3Q8mJ3b3UIwHAIBBQIBAQQUl4HQpV1vuJo7bxqhGZ1IHaNNf+4wHgIBCAIBAQQWFhQyMDIwLTAyLTAzVDEzOjIwOjI3WjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
  public $transaction_id =>
  string(15) "410000637286347"
  public $request_type =>
  string(6) "mobile"
  public $notification_type =>
  NULL
  public $email =>
  string(25) "quitowilliams@hotmail.com"
  public $brand =>
  string(6) "drumeo"
  public $valid =>
  int(1)
  public $validation_error =>
  NULL
  public $payment_id =>
  NULL
  public $subscription_id =>
  int(73924)
  public $raw_receipt_response =>
  string(23539) "O:42:"ReceiptValidator\\iTunes\\ProductionResponse":13:{s:14:"\000*\000result_code";i:0;s:12:"\000*\000bundle_id";s:23:"com.drumeo.DrumeoMobile";s:14:"\000*\000app_item_id";i:1460388277;s:25:"\000*\000original_purchase_date";O:13:"Carbon\\Carbon":3:{s:4:"date";s:26:"2020-02-03 13:18:06.000000";s:13:"timezone_type";i:1;s:8:"timezone";s:6:"+00:00";}s:15:"\000*\000request_date";O:13:"Carbon\\Carbon":3:{s:4:"date";s:26:"2020-02-03 13:20:28.000000";s:13:"timezone_type";i:1;s:8:"timezone";s:6:"+00:00";}s:24:"\000"...
  public $created_at =>
  string(19) "2020-02-03 13:20:27"
  public $updated_at =>
  string(19) "2020-02-03 13:20:29"
}
/app/ecommerce/src/Commands/FixSerializeErrorInAppPurchaseTables.php:127:
class ReceiptValidator\iTunes\ProductionResponse#2236 (13) {
  protected $result_code =>
  int(0)
  protected $bundle_id =>
  string(23) "com.drumeo.DrumeoMobile"
  protected $app_item_id =>
  int(1460388277)
  protected $original_purchase_date =>
  class Carbon\Carbon#2848 (3) {
    public $date =>
    string(26) "2020-02-03 13:18:06.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $request_date =>
  class Carbon\Carbon#2841 (3) {
    public $date =>
    string(26) "2020-02-03 13:20:28.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $receipt_creation_date =>
  class Carbon\Carbon#2845 (3) {
    public $date =>
    string(26) "2020-02-03 13:20:27.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $receipt =>
  array(18) {
    'receipt_type' =>
    string(10) "Production"
    'adam_id' =>
    int(1460388277)
    'app_item_id' =>
    int(1460388277)
    'bundle_id' =>
    string(23) "com.drumeo.DrumeoMobile"
    'application_version' =>
    string(7) "0.0.245"
    'download_id' =>
    int(81061853589770)
    'version_external_identifier' =>
    int(834486669)
    'receipt_creation_date' =>
    string(27) "2020-02-03 13:20:27 Etc/GMT"
    'receipt_creation_date_ms' =>
    string(13) "1580736027000"
    'receipt_creation_date_pst' =>
    string(39) "2020-02-03 05:20:27 America/Los_Angeles"
    'request_date' =>
    string(27) "2020-02-03 13:20:28 Etc/GMT"
    'request_date_ms' =>
    string(13) "1580736028408"
    'request_date_pst' =>
    string(39) "2020-02-03 05:20:28 America/Los_Angeles"
    'original_purchase_date' =>
    string(27) "2020-02-03 13:18:06 Etc/GMT"
    'original_purchase_date_ms' =>
    string(13) "1580735886000"
    'original_purchase_date_pst' =>
    string(39) "2020-02-03 05:18:06 America/Los_Angeles"
    'original_application_version' =>
    string(7) "0.0.245"
    'in_app' =>
    array(1) {
      [0] =>
      array(16) {
        ...
      }
    }
  }
  protected $latest_receipt =>
  string(6892) "MIIUKwYJKoZIhvcNAQcCoIIUHDCCFBgCAQExCzAJBgUrDgMCGgUAMIIDzAYJKoZIhvcNAQcBoIIDvQSCA7kxggO1MAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAKowDQIBDQIBAQQFAgMB1lEwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGSbmu/PEKMBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEIFNWD3hpM+DH3Q8mJ3b3UIwHAIBBQIBAQQUl4HQpV1vuJo7bxqhGZ1IHaNNf+4wHgIBCAIBAQQWFhQyMDIwLTAyLTAzVDEzOjIwOjI3WjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
  protected $latest_receipt_info =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PurchaseItem#2844 (13) {
      protected $quantity =>
      int(1)
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $web_order_line_item_id =>
      string(15) "410000233595884"
      protected $transaction_id =>
      string(15) "410000637286347"
      protected $original_transaction_id =>
      string(15) "410000637286347"
      protected $purchase_date =>
      class Carbon\Carbon#2832 (3) {
        ...
      }
      protected $original_purchase_date =>
      class Carbon\Carbon#2838 (3) {
        ...
      }
      protected $expires_date =>
      class Carbon\Carbon#2840 (3) {
        ...
      }
      protected $cancellation_date =>
      NULL
      protected $is_trial_period =>
      bool(true)
      protected $is_in_intro_offer_period =>
      bool(false)
      protected $promotional_offer_id =>
      NULL
      protected $raw_data =>
      array(17) {
        ...
      }
    }
  }
  protected $purchases =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PurchaseItem#2842 (13) {
      protected $quantity =>
      int(1)
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $web_order_line_item_id =>
      string(15) "410000233595884"
      protected $transaction_id =>
      string(15) "410000637286347"
      protected $original_transaction_id =>
      string(15) "410000637286347"
      protected $purchase_date =>
      class Carbon\Carbon#2831 (3) {
        ...
      }
      protected $original_purchase_date =>
      class Carbon\Carbon#2829 (3) {
        ...
      }
      protected $expires_date =>
      class Carbon\Carbon#2828 (3) {
        ...
      }
      protected $cancellation_date =>
      NULL
      protected $is_trial_period =>
      bool(true)
      protected $is_in_intro_offer_period =>
      bool(false)
      protected $promotional_offer_id =>
      NULL
      protected $raw_data =>
      array(16) {
        ...
      }
    }
  }
  protected $pending_renewal_info =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PendingRenewalInfo#2827 (7) {
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $auto_renew_product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $original_transaction_id =>
      string(15) "410000637286347"
      protected $auto_renew_status =>
      bool(true)
      protected $expiration_intent =>
      NULL
      protected $is_in_billing_retry_period =>
      NULL
      protected $raw_data =>
      array(4) {
        ...
      }
    }
  }
  protected $raw_data =>
  array(6) {
    'status' =>
    int(0)
    'environment' =>
    string(10) "Production"
    'receipt' =>
    array(18) {
      'receipt_type' =>
      string(10) "Production"
      'adam_id' =>
      int(1460388277)
      'app_item_id' =>
      int(1460388277)
      'bundle_id' =>
      string(23) "com.drumeo.DrumeoMobile"
      'application_version' =>
      string(7) "0.0.245"
      'download_id' =>
      int(81061853589770)
      'version_external_identifier' =>
      int(834486669)
      'receipt_creation_date' =>
      string(27) "2020-02-03 13:20:27 Etc/GMT"
      'receipt_creation_date_ms' =>
      string(13) "1580736027000"
      'receipt_creation_date_pst' =>
      string(39) "2020-02-03 05:20:27 America/Los_Angeles"
      'request_date' =>
      string(27) "2020-02-03 13:20:28 Etc/GMT"
      'request_date_ms' =>
      string(13) "1580736028408"
      'request_date_pst' =>
      string(39) "2020-02-03 05:20:28 America/Los_Angeles"
      'original_purchase_date' =>
      string(27) "2020-02-03 13:18:06 Etc/GMT"
      'original_purchase_date_ms' =>
      string(13) "1580735886000"
      'original_purchase_date_pst' =>
      string(39) "2020-02-03 05:18:06 America/Los_Angeles"
      'original_application_version' =>
      string(7) "0.0.245"
      'in_app' =>
      array(1) {
        ...
      }
    }
    'latest_receipt_info' =>
    array(1) {
      [0] =>
      array(17) {
        ...
      }
    }
    'latest_receipt' =>
    string(6892) "MIIUKwYJKoZIhvcNAQcCoIIUHDCCFBgCAQExCzAJBgUrDgMCGgUAMIIDzAYJKoZIhvcNAQcBoIIDvQSCA7kxggO1MAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAKowDQIBDQIBAQQFAgMB1lEwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGSbmu/PEKMBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEIFNWD3hpM+DH3Q8mJ3b3UIwHAIBBQIBAQQUl4HQpV1vuJo7bxqhGZ1IHaNNf+4wHgIBCAIBAQQWFhQyMDIwLTAyLTAzVDEzOjIwOjI3WjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
    'pending_renewal_info' =>
    array(1) {
      [0] =>
      array(4) {
        ...
      }
    }
  }
  protected $is_retryable =>
  bool(false)
}
---------------------------------------------------------
/app/ecommerce/src/Commands/FixSerializeErrorInAppPurchaseTables.php:126:
class stdClass#2800 (14) {
  public $id =>
  int(544)
  public $receipt =>
  string(6880) "MIIUJAYJKoZIhvcNAQcCoIIUFTCCFBECAQExCzAJBgUrDgMCGgUAMIIDxQYJKoZIhvcNAQcBoIIDtgSCA7IxggOuMAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICALkwDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGSqJwwnXdMBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEBg3wPqFLf/YY9YR+9GSDqEwHAIBBQIBAQQUE+ssVqDOTkum3+dIZbHuCtWn4QQwHgIBCAIBAQQWFhQyMDIwLTAyLTAzVDEzOjIwOjMwWjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
  public $transaction_id =>
  string(15) "420000620739977"
  public $request_type =>
  string(6) "mobile"
  public $notification_type =>
  NULL
  public $email =>
  string(17) "klupino@gmail.com"
  public $brand =>
  string(6) "drumeo"
  public $valid =>
  int(1)
  public $validation_error =>
  NULL
  public $payment_id =>
  NULL
  public $subscription_id =>
  int(73925)
  public $raw_receipt_response =>
  string(23475) "O:42:"ReceiptValidator\\iTunes\\ProductionResponse":13:{s:14:"\000*\000result_code";i:0;s:12:"\000*\000bundle_id";s:23:"com.drumeo.DrumeoMobile";s:14:"\000*\000app_item_id";i:1460388277;s:25:"\000*\000original_purchase_date";O:13:"Carbon\\Carbon":3:{s:4:"date";s:26:"2020-02-03 13:18:47.000000";s:13:"timezone_type";i:1;s:8:"timezone";s:6:"+00:00";}s:15:"\000*\000request_date";O:13:"Carbon\\Carbon":3:{s:4:"date";s:26:"2020-02-03 13:20:31.000000";s:13:"timezone_type";i:1;s:8:"timezone";s:6:"+00:00";}s:24:"\000"...
  public $created_at =>
  string(19) "2020-02-03 13:20:30"
  public $updated_at =>
  string(19) "2020-02-03 13:20:31"
}
/app/ecommerce/src/Commands/FixSerializeErrorInAppPurchaseTables.php:127:
class ReceiptValidator\iTunes\ProductionResponse#2236 (13) {
  protected $result_code =>
  int(0)
  protected $bundle_id =>
  string(23) "com.drumeo.DrumeoMobile"
  protected $app_item_id =>
  int(1460388277)
  protected $original_purchase_date =>
  class Carbon\Carbon#2827 (3) {
    public $date =>
    string(26) "2020-02-03 13:18:47.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $request_date =>
  class Carbon\Carbon#2842 (3) {
    public $date =>
    string(26) "2020-02-03 13:20:31.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $receipt_creation_date =>
  class Carbon\Carbon#2828 (3) {
    public $date =>
    string(26) "2020-02-03 13:20:30.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $receipt =>
  array(18) {
    'receipt_type' =>
    string(10) "Production"
    'adam_id' =>
    int(1460388277)
    'app_item_id' =>
    int(1460388277)
    'bundle_id' =>
    string(23) "com.drumeo.DrumeoMobile"
    'application_version' =>
    string(7) "0.0.245"
    'download_id' =>
    int(82061536949725)
    'version_external_identifier' =>
    int(834486669)
    'receipt_creation_date' =>
    string(27) "2020-02-03 13:20:30 Etc/GMT"
    'receipt_creation_date_ms' =>
    string(13) "1580736030000"
    'receipt_creation_date_pst' =>
    string(39) "2020-02-03 05:20:30 America/Los_Angeles"
    'request_date' =>
    string(27) "2020-02-03 13:20:31 Etc/GMT"
    'request_date_ms' =>
    string(13) "1580736031177"
    'request_date_pst' =>
    string(39) "2020-02-03 05:20:31 America/Los_Angeles"
    'original_purchase_date' =>
    string(27) "2020-02-03 13:18:47 Etc/GMT"
    'original_purchase_date_ms' =>
    string(13) "1580735927000"
    'original_purchase_date_pst' =>
    string(39) "2020-02-03 05:18:47 America/Los_Angeles"
    'original_application_version' =>
    string(7) "0.0.245"
    'in_app' =>
    array(1) {
      [0] =>
      array(16) {
        ...
      }
    }
  }
  protected $latest_receipt =>
  string(6860) "MIIUFAYJKoZIhvcNAQcCoIIUBTCCFAECAQExCzAJBgUrDgMCGgUAMIIDtQYJKoZIhvcNAQcBoIIDpgSCA6IxggOeMAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICALkwDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGSqJwwnXdMBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEBg3wPqFLf/YY9YR+9GSDqEwHAIBBQIBAQQUE+ssVqDOTkum3+dIZbHuCtWn4QQwHgIBCAIBAQQWFhQyMDIwLTAyLTAzVDEzOjIwOjMwWjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
  protected $latest_receipt_info =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PurchaseItem#2829 (13) {
      protected $quantity =>
      int(1)
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $web_order_line_item_id =>
      string(15) "420000228429089"
      protected $transaction_id =>
      string(15) "420000620739977"
      protected $original_transaction_id =>
      string(15) "420000620739977"
      protected $purchase_date =>
      class Carbon\Carbon#2831 (3) {
        ...
      }
      protected $original_purchase_date =>
      class Carbon\Carbon#2844 (3) {
        ...
      }
      protected $expires_date =>
      class Carbon\Carbon#2840 (3) {
        ...
      }
      protected $cancellation_date =>
      NULL
      protected $is_trial_period =>
      bool(true)
      protected $is_in_intro_offer_period =>
      bool(false)
      protected $promotional_offer_id =>
      NULL
      protected $raw_data =>
      array(17) {
        ...
      }
    }
  }
  protected $purchases =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PurchaseItem#2838 (13) {
      protected $quantity =>
      int(1)
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $web_order_line_item_id =>
      string(15) "420000228429089"
      protected $transaction_id =>
      string(15) "420000620739977"
      protected $original_transaction_id =>
      string(15) "420000620739977"
      protected $purchase_date =>
      class Carbon\Carbon#2832 (3) {
        ...
      }
      protected $original_purchase_date =>
      class Carbon\Carbon#2845 (3) {
        ...
      }
      protected $expires_date =>
      class Carbon\Carbon#2841 (3) {
        ...
      }
      protected $cancellation_date =>
      NULL
      protected $is_trial_period =>
      bool(true)
      protected $is_in_intro_offer_period =>
      bool(false)
      protected $promotional_offer_id =>
      NULL
      protected $raw_data =>
      array(16) {
        ...
      }
    }
  }
  protected $pending_renewal_info =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PendingRenewalInfo#2848 (7) {
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $auto_renew_product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $original_transaction_id =>
      string(15) "420000620739977"
      protected $auto_renew_status =>
      bool(true)
      protected $expiration_intent =>
      NULL
      protected $is_in_billing_retry_period =>
      NULL
      protected $raw_data =>
      array(4) {
        ...
      }
    }
  }
  protected $raw_data =>
  array(6) {
    'status' =>
    int(0)
    'environment' =>
    string(10) "Production"
    'receipt' =>
    array(18) {
      'receipt_type' =>
      string(10) "Production"
      'adam_id' =>
      int(1460388277)
      'app_item_id' =>
      int(1460388277)
      'bundle_id' =>
      string(23) "com.drumeo.DrumeoMobile"
      'application_version' =>
      string(7) "0.0.245"
      'download_id' =>
      int(82061536949725)
      'version_external_identifier' =>
      int(834486669)
      'receipt_creation_date' =>
      string(27) "2020-02-03 13:20:30 Etc/GMT"
      'receipt_creation_date_ms' =>
      string(13) "1580736030000"
      'receipt_creation_date_pst' =>
      string(39) "2020-02-03 05:20:30 America/Los_Angeles"
      'request_date' =>
      string(27) "2020-02-03 13:20:31 Etc/GMT"
      'request_date_ms' =>
      string(13) "1580736031177"
      'request_date_pst' =>
      string(39) "2020-02-03 05:20:31 America/Los_Angeles"
      'original_purchase_date' =>
      string(27) "2020-02-03 13:18:47 Etc/GMT"
      'original_purchase_date_ms' =>
      string(13) "1580735927000"
      'original_purchase_date_pst' =>
      string(39) "2020-02-03 05:18:47 America/Los_Angeles"
      'original_application_version' =>
      string(7) "0.0.245"
      'in_app' =>
      array(1) {
        ...
      }
    }
    'latest_receipt_info' =>
    array(1) {
      [0] =>
      array(17) {
        ...
      }
    }
    'latest_receipt' =>
    string(6860) "MIIUFAYJKoZIhvcNAQcCoIIUBTCCFAECAQExCzAJBgUrDgMCGgUAMIIDtQYJKoZIhvcNAQcBoIIDpgSCA6IxggOeMAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICALkwDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGSqJwwnXdMBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEBg3wPqFLf/YY9YR+9GSDqEwHAIBBQIBAQQUE+ssVqDOTkum3+dIZbHuCtWn4QQwHgIBCAIBAQQWFhQyMDIwLTAyLTAzVDEzOjIwOjMwWjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
    'pending_renewal_info' =>
    array(1) {
      [0] =>
      array(4) {
        ...
      }
    }
  }
  protected $is_retryable =>
  bool(false)
}
---------------------------------------------------------
/app/ecommerce/src/Commands/FixSerializeErrorInAppPurchaseTables.php:126:
class stdClass#2801 (14) {
  public $id =>
  int(545)
  public $receipt =>
  string(6908) "MIIUOAYJKoZIhvcNAQcCoIIUKTCCFCUCAQExCzAJBgUrDgMCGgUAMIID2QYJKoZIhvcNAQcBoIIDygSCA8YxggPCMAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAMswDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGZP/SvX4HMBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEHkDftpl4caLc6Tk8IudaogwHAIBBQIBAQQUQLllV01+DYPpEogTHUpqPAJYcjowHgIBCAIBAQQWFhQyMDIwLTAyLTAzVDE3OjI0OjIwWjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
  public $transaction_id =>
  string(15) "710000497586339"
  public $request_type =>
  string(6) "mobile"
  public $notification_type =>
  NULL
  public $email =>
  string(22) "savanaemmert@gmail.com"
  public $brand =>
  string(6) "drumeo"
  public $valid =>
  int(1)
  public $validation_error =>
  NULL
  public $payment_id =>
  NULL
  public $subscription_id =>
  int(73932)
  public $raw_receipt_response =>
  string(23541) "O:42:"ReceiptValidator\\iTunes\\ProductionResponse":13:{s:14:"\000*\000result_code";i:0;s:12:"\000*\000bundle_id";s:23:"com.drumeo.DrumeoMobile";s:14:"\000*\000app_item_id";i:1460388277;s:25:"\000*\000original_purchase_date";O:13:"Carbon\\Carbon":3:{s:4:"date";s:26:"2020-02-03 17:21:18.000000";s:13:"timezone_type";i:1;s:8:"timezone";s:6:"+00:00";}s:15:"\000*\000request_date";O:13:"Carbon\\Carbon":3:{s:4:"date";s:26:"2020-02-03 17:24:22.000000";s:13:"timezone_type";i:1;s:8:"timezone";s:6:"+00:00";}s:24:"\000"...
  public $created_at =>
  string(19) "2020-02-03 17:24:21"
  public $updated_at =>
  string(19) "2020-02-03 17:24:22"
}
/app/ecommerce/src/Commands/FixSerializeErrorInAppPurchaseTables.php:127:
class ReceiptValidator\iTunes\ProductionResponse#2236 (13) {
  protected $result_code =>
  int(0)
  protected $bundle_id =>
  string(23) "com.drumeo.DrumeoMobile"
  protected $app_item_id =>
  int(1460388277)
  protected $original_purchase_date =>
  class Carbon\Carbon#2848 (3) {
    public $date =>
    string(26) "2020-02-03 17:21:18.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $request_date =>
  class Carbon\Carbon#2838 (3) {
    public $date =>
    string(26) "2020-02-03 17:24:22.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $receipt_creation_date =>
  class Carbon\Carbon#2841 (3) {
    public $date =>
    string(26) "2020-02-03 17:24:20.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $receipt =>
  array(18) {
    'receipt_type' =>
    string(10) "Production"
    'adam_id' =>
    int(1460388277)
    'app_item_id' =>
    int(1460388277)
    'bundle_id' =>
    string(23) "com.drumeo.DrumeoMobile"
    'application_version' =>
    string(7) "0.0.245"
    'download_id' =>
    int(111049915072007)
    'version_external_identifier' =>
    int(834486669)
    'receipt_creation_date' =>
    string(27) "2020-02-03 17:24:20 Etc/GMT"
    'receipt_creation_date_ms' =>
    string(13) "1580750660000"
    'receipt_creation_date_pst' =>
    string(39) "2020-02-03 09:24:20 America/Los_Angeles"
    'request_date' =>
    string(27) "2020-02-03 17:24:21 Etc/GMT"
    'request_date_ms' =>
    string(13) "1580750661712"
    'request_date_pst' =>
    string(39) "2020-02-03 09:24:21 America/Los_Angeles"
    'original_purchase_date' =>
    string(27) "2020-02-03 17:21:18 Etc/GMT"
    'original_purchase_date_ms' =>
    string(13) "1580750478000"
    'original_purchase_date_pst' =>
    string(39) "2020-02-03 09:21:18 America/Los_Angeles"
    'original_application_version' =>
    string(7) "0.0.245"
    'in_app' =>
    array(1) {
      [0] =>
      array(16) {
        ...
      }
    }
  }
  protected $latest_receipt =>
  string(6892) "MIIUKwYJKoZIhvcNAQcCoIIUHDCCFBgCAQExCzAJBgUrDgMCGgUAMIIDzAYJKoZIhvcNAQcBoIIDvQSCA7kxggO1MAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAMswDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGZP/SvX4HMBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEHkDftpl4caLc6Tk8IudaogwHAIBBQIBAQQUQLllV01+DYPpEogTHUpqPAJYcjowHgIBCAIBAQQWFhQyMDIwLTAyLTAzVDE3OjI0OjIwWjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
  protected $latest_receipt_info =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PurchaseItem#2845 (13) {
      protected $quantity =>
      int(1)
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $web_order_line_item_id =>
      string(15) "710000178724254"
      protected $transaction_id =>
      string(15) "710000497586339"
      protected $original_transaction_id =>
      string(15) "710000497586339"
      protected $purchase_date =>
      class Carbon\Carbon#2832 (3) {
        ...
      }
      protected $original_purchase_date =>
      class Carbon\Carbon#2829 (3) {
        ...
      }
      protected $expires_date =>
      class Carbon\Carbon#2840 (3) {
        ...
      }
      protected $cancellation_date =>
      NULL
      protected $is_trial_period =>
      bool(true)
      protected $is_in_intro_offer_period =>
      bool(false)
      protected $promotional_offer_id =>
      NULL
      protected $raw_data =>
      array(17) {
        ...
      }
    }
  }
  protected $purchases =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PurchaseItem#2844 (13) {
      protected $quantity =>
      int(1)
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $web_order_line_item_id =>
      string(15) "710000178724254"
      protected $transaction_id =>
      string(15) "710000497586339"
      protected $original_transaction_id =>
      string(15) "710000497586339"
      protected $purchase_date =>
      class Carbon\Carbon#2831 (3) {
        ...
      }
      protected $original_purchase_date =>
      class Carbon\Carbon#2828 (3) {
        ...
      }
      protected $expires_date =>
      class Carbon\Carbon#2842 (3) {
        ...
      }
      protected $cancellation_date =>
      NULL
      protected $is_trial_period =>
      bool(true)
      protected $is_in_intro_offer_period =>
      bool(false)
      protected $promotional_offer_id =>
      NULL
      protected $raw_data =>
      array(16) {
        ...
      }
    }
  }
  protected $pending_renewal_info =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PendingRenewalInfo#2827 (7) {
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $auto_renew_product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $original_transaction_id =>
      string(15) "710000497586339"
      protected $auto_renew_status =>
      bool(true)
      protected $expiration_intent =>
      NULL
      protected $is_in_billing_retry_period =>
      NULL
      protected $raw_data =>
      array(4) {
        ...
      }
    }
  }
  protected $raw_data =>
  array(6) {
    'status' =>
    int(0)
    'environment' =>
    string(10) "Production"
    'receipt' =>
    array(18) {
      'receipt_type' =>
      string(10) "Production"
      'adam_id' =>
      int(1460388277)
      'app_item_id' =>
      int(1460388277)
      'bundle_id' =>
      string(23) "com.drumeo.DrumeoMobile"
      'application_version' =>
      string(7) "0.0.245"
      'download_id' =>
      int(111049915072007)
      'version_external_identifier' =>
      int(834486669)
      'receipt_creation_date' =>
      string(27) "2020-02-03 17:24:20 Etc/GMT"
      'receipt_creation_date_ms' =>
      string(13) "1580750660000"
      'receipt_creation_date_pst' =>
      string(39) "2020-02-03 09:24:20 America/Los_Angeles"
      'request_date' =>
      string(27) "2020-02-03 17:24:21 Etc/GMT"
      'request_date_ms' =>
      string(13) "1580750661712"
      'request_date_pst' =>
      string(39) "2020-02-03 09:24:21 America/Los_Angeles"
      'original_purchase_date' =>
      string(27) "2020-02-03 17:21:18 Etc/GMT"
      'original_purchase_date_ms' =>
      string(13) "1580750478000"
      'original_purchase_date_pst' =>
      string(39) "2020-02-03 09:21:18 America/Los_Angeles"
      'original_application_version' =>
      string(7) "0.0.245"
      'in_app' =>
      array(1) {
        ...
      }
    }
    'latest_receipt_info' =>
    array(1) {
      [0] =>
      array(17) {
        ...
      }
    }
    'latest_receipt' =>
    string(6892) "MIIUKwYJKoZIhvcNAQcCoIIUHDCCFBgCAQExCzAJBgUrDgMCGgUAMIIDzAYJKoZIhvcNAQcBoIIDvQSCA7kxggO1MAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAMswDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGZP/SvX4HMBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEHkDftpl4caLc6Tk8IudaogwHAIBBQIBAQQUQLllV01+DYPpEogTHUpqPAJYcjowHgIBCAIBAQQWFhQyMDIwLTAyLTAzVDE3OjI0OjIwWjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
    'pending_renewal_info' =>
    array(1) {
      [0] =>
      array(4) {
        ...
      }
    }
  }
  protected $is_retryable =>
  bool(false)
}
---------------------------------------------------------
/app/ecommerce/src/Commands/FixSerializeErrorInAppPurchaseTables.php:126:
class stdClass#2802 (14) {
  public $id =>
  int(546)
  public $receipt =>
  string(6900) "MIIUMgYJKoZIhvcNAQcCoIIUIzCCFB8CAQExCzAJBgUrDgMCGgUAMIID0wYJKoZIhvcNAQcBoIIDxASCA8AxggO8MAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAIowDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGOHL+90pfMBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEFm/C5xELp9rAauz3iO2SnowHAIBBQIBAQQUZHiyro5zPZp62M5QFMUPQbvtGBkwHgIBCAIBAQQWFhQyMDIwLTAyLTAzVDE4OjA1OjM0WjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
  public $transaction_id =>
  string(15) "220000694904748"
  public $request_type =>
  string(6) "mobile"
  public $notification_type =>
  NULL
  public $email =>
  string(18) "josugr@hotmail.com"
  public $brand =>
  string(6) "drumeo"
  public $valid =>
  int(1)
  public $validation_error =>
  NULL
  public $payment_id =>
  NULL
  public $subscription_id =>
  int(73934)
  public $raw_receipt_response =>
  string(23534) "O:42:"ReceiptValidator\\iTunes\\ProductionResponse":13:{s:14:"\000*\000result_code";i:0;s:12:"\000*\000bundle_id";s:23:"com.drumeo.DrumeoMobile";s:14:"\000*\000app_item_id";i:1460388277;s:25:"\000*\000original_purchase_date";O:13:"Carbon\\Carbon":3:{s:4:"date";s:26:"2020-02-03 18:04:04.000000";s:13:"timezone_type";i:1;s:8:"timezone";s:6:"+00:00";}s:15:"\000*\000request_date";O:13:"Carbon\\Carbon":3:{s:4:"date";s:26:"2020-02-03 18:05:36.000000";s:13:"timezone_type";i:1;s:8:"timezone";s:6:"+00:00";}s:24:"\000"...
  public $created_at =>
  string(19) "2020-02-03 18:05:35"
  public $updated_at =>
  string(19) "2020-02-03 18:05:36"
}
/app/ecommerce/src/Commands/FixSerializeErrorInAppPurchaseTables.php:127:
class ReceiptValidator\iTunes\ProductionResponse#2236 (13) {
  protected $result_code =>
  int(0)
  protected $bundle_id =>
  string(23) "com.drumeo.DrumeoMobile"
  protected $app_item_id =>
  int(1460388277)
  protected $original_purchase_date =>
  class Carbon\Carbon#2827 (3) {
    public $date =>
    string(26) "2020-02-03 18:04:04.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $request_date =>
  class Carbon\Carbon#2844 (3) {
    public $date =>
    string(26) "2020-02-03 18:05:36.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $receipt_creation_date =>
  class Carbon\Carbon#2842 (3) {
    public $date =>
    string(26) "2020-02-03 18:05:34.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $receipt =>
  array(18) {
    'receipt_type' =>
    string(10) "Production"
    'adam_id' =>
    int(1460388277)
    'app_item_id' =>
    int(1460388277)
    'bundle_id' =>
    string(23) "com.drumeo.DrumeoMobile"
    'application_version' =>
    string(7) "0.0.245"
    'download_id' =>
    int(62066555046495)
    'version_external_identifier' =>
    int(834486669)
    'receipt_creation_date' =>
    string(27) "2020-02-03 18:05:34 Etc/GMT"
    'receipt_creation_date_ms' =>
    string(13) "1580753134000"
    'receipt_creation_date_pst' =>
    string(39) "2020-02-03 10:05:34 America/Los_Angeles"
    'request_date' =>
    string(27) "2020-02-03 18:05:35 Etc/GMT"
    'request_date_ms' =>
    string(13) "1580753135881"
    'request_date_pst' =>
    string(39) "2020-02-03 10:05:35 America/Los_Angeles"
    'original_purchase_date' =>
    string(27) "2020-02-03 18:04:04 Etc/GMT"
    'original_purchase_date_ms' =>
    string(13) "1580753044000"
    'original_purchase_date_pst' =>
    string(39) "2020-02-03 10:04:04 America/Los_Angeles"
    'original_application_version' =>
    string(7) "0.0.245"
    'in_app' =>
    array(1) {
      [0] =>
      array(16) {
        ...
      }
    }
  }
  protected $latest_receipt =>
  string(6896) "MIIUMAYJKoZIhvcNAQcCoIIUITCCFB0CAQExCzAJBgUrDgMCGgUAMIID0QYJKoZIhvcNAQcBoIIDwgSCA74xggO6MAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAIowDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGOHL+90pfMBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEFm/C5xELp9rAauz3iO2SnowHAIBBQIBAQQUZHiyro5zPZp62M5QFMUPQbvtGBkwHgIBCAIBAQQWFhQyMDIwLTAyLTAzVDE4OjA1OjM0WjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
  protected $latest_receipt_info =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PurchaseItem#2828 (13) {
      protected $quantity =>
      int(1)
      protected $product_id =>
      string(24) "drumeo_app_1_year_member"
      protected $web_order_line_item_id =>
      string(15) "220000246368324"
      protected $transaction_id =>
      string(15) "220000694904748"
      protected $original_transaction_id =>
      string(15) "220000694904748"
      protected $purchase_date =>
      class Carbon\Carbon#2831 (3) {
        ...
      }
      protected $original_purchase_date =>
      class Carbon\Carbon#2845 (3) {
        ...
      }
      protected $expires_date =>
      class Carbon\Carbon#2840 (3) {
        ...
      }
      protected $cancellation_date =>
      NULL
      protected $is_trial_period =>
      bool(true)
      protected $is_in_intro_offer_period =>
      bool(false)
      protected $promotional_offer_id =>
      NULL
      protected $raw_data =>
      array(17) {
        ...
      }
    }
  }
  protected $purchases =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PurchaseItem#2829 (13) {
      protected $quantity =>
      int(1)
      protected $product_id =>
      string(24) "drumeo_app_1_year_member"
      protected $web_order_line_item_id =>
      string(15) "220000246368324"
      protected $transaction_id =>
      string(15) "220000694904748"
      protected $original_transaction_id =>
      string(15) "220000694904748"
      protected $purchase_date =>
      class Carbon\Carbon#2832 (3) {
        ...
      }
      protected $original_purchase_date =>
      class Carbon\Carbon#2841 (3) {
        ...
      }
      protected $expires_date =>
      class Carbon\Carbon#2838 (3) {
        ...
      }
      protected $cancellation_date =>
      NULL
      protected $is_trial_period =>
      bool(true)
      protected $is_in_intro_offer_period =>
      bool(false)
      protected $promotional_offer_id =>
      NULL
      protected $raw_data =>
      array(16) {
        ...
      }
    }
  }
  protected $pending_renewal_info =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PendingRenewalInfo#2848 (7) {
      protected $product_id =>
      string(24) "drumeo_app_1_year_member"
      protected $auto_renew_product_id =>
      string(24) "drumeo_app_1_year_member"
      protected $original_transaction_id =>
      string(15) "220000694904748"
      protected $auto_renew_status =>
      bool(true)
      protected $expiration_intent =>
      NULL
      protected $is_in_billing_retry_period =>
      NULL
      protected $raw_data =>
      array(4) {
        ...
      }
    }
  }
  protected $raw_data =>
  array(6) {
    'status' =>
    int(0)
    'environment' =>
    string(10) "Production"
    'receipt' =>
    array(18) {
      'receipt_type' =>
      string(10) "Production"
      'adam_id' =>
      int(1460388277)
      'app_item_id' =>
      int(1460388277)
      'bundle_id' =>
      string(23) "com.drumeo.DrumeoMobile"
      'application_version' =>
      string(7) "0.0.245"
      'download_id' =>
      int(62066555046495)
      'version_external_identifier' =>
      int(834486669)
      'receipt_creation_date' =>
      string(27) "2020-02-03 18:05:34 Etc/GMT"
      'receipt_creation_date_ms' =>
      string(13) "1580753134000"
      'receipt_creation_date_pst' =>
      string(39) "2020-02-03 10:05:34 America/Los_Angeles"
      'request_date' =>
      string(27) "2020-02-03 18:05:35 Etc/GMT"
      'request_date_ms' =>
      string(13) "1580753135881"
      'request_date_pst' =>
      string(39) "2020-02-03 10:05:35 America/Los_Angeles"
      'original_purchase_date' =>
      string(27) "2020-02-03 18:04:04 Etc/GMT"
      'original_purchase_date_ms' =>
      string(13) "1580753044000"
      'original_purchase_date_pst' =>
      string(39) "2020-02-03 10:04:04 America/Los_Angeles"
      'original_application_version' =>
      string(7) "0.0.245"
      'in_app' =>
      array(1) {
        ...
      }
    }
    'latest_receipt_info' =>
    array(1) {
      [0] =>
      array(17) {
        ...
      }
    }
    'latest_receipt' =>
    string(6896) "MIIUMAYJKoZIhvcNAQcCoIIUITCCFB0CAQExCzAJBgUrDgMCGgUAMIID0QYJKoZIhvcNAQcBoIIDwgSCA74xggO6MAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAIowDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGOHL+90pfMBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEFm/C5xELp9rAauz3iO2SnowHAIBBQIBAQQUZHiyro5zPZp62M5QFMUPQbvtGBkwHgIBCAIBAQQWFhQyMDIwLTAyLTAzVDE4OjA1OjM0WjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
    'pending_renewal_info' =>
    array(1) {
      [0] =>
      array(4) {
        ...
      }
    }
  }
  protected $is_retryable =>
  bool(false)
}
---------------------------------------------------------
/app/ecommerce/src/Commands/FixSerializeErrorInAppPurchaseTables.php:126:
class stdClass#2803 (14) {
  public $id =>
  int(547)
  public $receipt =>
  string(6860) "MIIUFQYJKoZIhvcNAQcCoIIUBjCCFAICAQExCzAJBgUrDgMCGgUAMIIDtgYJKoZIhvcNAQcBoIIDpwSCA6MxggOfMAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAKowDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGZebL/cyKMBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEHlwyiFCdsmaiIqOdP1zCGQwHAIBBQIBAQQU9gBEKuynftiebg9MRjff1yXTZBowHgIBCAIBAQQWFhQyMDIwLTAyLTAzVDE5OjAyOjI2WjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
  public $transaction_id =>
  string(15) "720000422446909"
  public $request_type =>
  string(6) "mobile"
  public $notification_type =>
  NULL
  public $email =>
  string(22) "hiba.tarazi1@gmail.com"
  public $brand =>
  string(6) "drumeo"
  public $valid =>
  int(1)
  public $validation_error =>
  NULL
  public $payment_id =>
  NULL
  public $subscription_id =>
  int(73936)
  public $raw_receipt_response =>
  string(23445) "O:42:"ReceiptValidator\\iTunes\\ProductionResponse":13:{s:14:"\000*\000result_code";i:0;s:12:"\000*\000bundle_id";s:23:"com.drumeo.DrumeoMobile";s:14:"\000*\000app_item_id";i:1460388277;s:25:"\000*\000original_purchase_date";O:13:"Carbon\\Carbon":3:{s:4:"date";s:26:"2020-02-03 18:59:16.000000";s:13:"timezone_type";i:1;s:8:"timezone";s:6:"+00:00";}s:15:"\000*\000request_date";O:13:"Carbon\\Carbon":3:{s:4:"date";s:26:"2020-02-03 19:02:28.000000";s:13:"timezone_type";i:1;s:8:"timezone";s:6:"+00:00";}s:24:"\000"...
  public $created_at =>
  string(19) "2020-02-03 19:02:27"
  public $updated_at =>
  string(19) "2020-02-03 19:02:28"
}
/app/ecommerce/src/Commands/FixSerializeErrorInAppPurchaseTables.php:127:
class ReceiptValidator\iTunes\ProductionResponse#2236 (13) {
  protected $result_code =>
  int(0)
  protected $bundle_id =>
  string(23) "com.drumeo.DrumeoMobile"
  protected $app_item_id =>
  int(1460388277)
  protected $original_purchase_date =>
  class Carbon\Carbon#2848 (3) {
    public $date =>
    string(26) "2020-02-03 18:59:16.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $request_date =>
  class Carbon\Carbon#2829 (3) {
    public $date =>
    string(26) "2020-02-03 19:02:28.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $receipt_creation_date =>
  class Carbon\Carbon#2838 (3) {
    public $date =>
    string(26) "2020-02-03 19:02:26.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $receipt =>
  array(18) {
    'receipt_type' =>
    string(10) "Production"
    'adam_id' =>
    int(1460388277)
    'app_item_id' =>
    int(1460388277)
    'bundle_id' =>
    string(23) "com.drumeo.DrumeoMobile"
    'application_version' =>
    string(7) "0.0.245"
    'download_id' =>
    int(112041939291274)
    'version_external_identifier' =>
    int(834486669)
    'receipt_creation_date' =>
    string(27) "2020-02-03 19:02:26 Etc/GMT"
    'receipt_creation_date_ms' =>
    string(13) "1580756546000"
    'receipt_creation_date_pst' =>
    string(39) "2020-02-03 11:02:26 America/Los_Angeles"
    'request_date' =>
    string(27) "2020-02-03 19:02:27 Etc/GMT"
    'request_date_ms' =>
    string(13) "1580756547925"
    'request_date_pst' =>
    string(39) "2020-02-03 11:02:27 America/Los_Angeles"
    'original_purchase_date' =>
    string(27) "2020-02-03 18:59:16 Etc/GMT"
    'original_purchase_date_ms' =>
    string(13) "1580756356000"
    'original_purchase_date_pst' =>
    string(39) "2020-02-03 10:59:16 America/Los_Angeles"
    'original_application_version' =>
    string(7) "0.0.245"
    'in_app' =>
    array(1) {
      [0] =>
      array(16) {
        ...
      }
    }
  }
  protected $latest_receipt =>
  string(6844) "MIIUCQYJKoZIhvcNAQcCoIIT+jCCE/YCAQExCzAJBgUrDgMCGgUAMIIDqgYJKoZIhvcNAQcBoIIDmwSCA5cxggOTMAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAKowDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGZebL/cyKMBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEHlwyiFCdsmaiIqOdP1zCGQwHAIBBQIBAQQU9gBEKuynftiebg9MRjff1yXTZBowHgIBCAIBAQQWFhQyMDIwLTAyLTAzVDE5OjAyOjI2WjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
  protected $latest_receipt_info =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PurchaseItem#2841 (13) {
      protected $quantity =>
      int(1)
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $web_order_line_item_id =>
      string(15) "720000147004787"
      protected $transaction_id =>
      string(15) "720000422446909"
      protected $original_transaction_id =>
      string(15) "720000422446909"
      protected $purchase_date =>
      class Carbon\Carbon#2832 (3) {
        ...
      }
      protected $original_purchase_date =>
      class Carbon\Carbon#2828 (3) {
        ...
      }
      protected $expires_date =>
      class Carbon\Carbon#2840 (3) {
        ...
      }
      protected $cancellation_date =>
      NULL
      protected $is_trial_period =>
      bool(true)
      protected $is_in_intro_offer_period =>
      bool(false)
      protected $promotional_offer_id =>
      NULL
      protected $raw_data =>
      array(17) {
        ...
      }
    }
  }
  protected $purchases =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PurchaseItem#2845 (13) {
      protected $quantity =>
      int(1)
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $web_order_line_item_id =>
      string(15) "720000147004787"
      protected $transaction_id =>
      string(15) "720000422446909"
      protected $original_transaction_id =>
      string(15) "720000422446909"
      protected $purchase_date =>
      class Carbon\Carbon#2831 (3) {
        ...
      }
      protected $original_purchase_date =>
      class Carbon\Carbon#2842 (3) {
        ...
      }
      protected $expires_date =>
      class Carbon\Carbon#2844 (3) {
        ...
      }
      protected $cancellation_date =>
      NULL
      protected $is_trial_period =>
      bool(true)
      protected $is_in_intro_offer_period =>
      bool(false)
      protected $promotional_offer_id =>
      NULL
      protected $raw_data =>
      array(16) {
        ...
      }
    }
  }
  protected $pending_renewal_info =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PendingRenewalInfo#2827 (7) {
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $auto_renew_product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $original_transaction_id =>
      string(15) "720000422446909"
      protected $auto_renew_status =>
      bool(true)
      protected $expiration_intent =>
      NULL
      protected $is_in_billing_retry_period =>
      NULL
      protected $raw_data =>
      array(4) {
        ...
      }
    }
  }
  protected $raw_data =>
  array(6) {
    'status' =>
    int(0)
    'environment' =>
    string(10) "Production"
    'receipt' =>
    array(18) {
      'receipt_type' =>
      string(10) "Production"
      'adam_id' =>
      int(1460388277)
      'app_item_id' =>
      int(1460388277)
      'bundle_id' =>
      string(23) "com.drumeo.DrumeoMobile"
      'application_version' =>
      string(7) "0.0.245"
      'download_id' =>
      int(112041939291274)
      'version_external_identifier' =>
      int(834486669)
      'receipt_creation_date' =>
      string(27) "2020-02-03 19:02:26 Etc/GMT"
      'receipt_creation_date_ms' =>
      string(13) "1580756546000"
      'receipt_creation_date_pst' =>
      string(39) "2020-02-03 11:02:26 America/Los_Angeles"
      'request_date' =>
      string(27) "2020-02-03 19:02:27 Etc/GMT"
      'request_date_ms' =>
      string(13) "1580756547925"
      'request_date_pst' =>
      string(39) "2020-02-03 11:02:27 America/Los_Angeles"
      'original_purchase_date' =>
      string(27) "2020-02-03 18:59:16 Etc/GMT"
      'original_purchase_date_ms' =>
      string(13) "1580756356000"
      'original_purchase_date_pst' =>
      string(39) "2020-02-03 10:59:16 America/Los_Angeles"
      'original_application_version' =>
      string(7) "0.0.245"
      'in_app' =>
      array(1) {
        ...
      }
    }
    'latest_receipt_info' =>
    array(1) {
      [0] =>
      array(17) {
        ...
      }
    }
    'latest_receipt' =>
    string(6844) "MIIUCQYJKoZIhvcNAQcCoIIT+jCCE/YCAQExCzAJBgUrDgMCGgUAMIIDqgYJKoZIhvcNAQcBoIIDmwSCA5cxggOTMAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAKowDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGZebL/cyKMBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEHlwyiFCdsmaiIqOdP1zCGQwHAIBBQIBAQQU9gBEKuynftiebg9MRjff1yXTZBowHgIBCAIBAQQWFhQyMDIwLTAyLTAzVDE5OjAyOjI2WjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
    'pending_renewal_info' =>
    array(1) {
      [0] =>
      array(4) {
        ...
      }
    }
  }
  protected $is_retryable =>
  bool(false)
}
---------------------------------------------------------
/app/ecommerce/src/Commands/FixSerializeErrorInAppPurchaseTables.php:126:
class stdClass#2804 (14) {
  public $id =>
  int(548)
  public $receipt =>
  string(6880) "MIIUJAYJKoZIhvcNAQcCoIIUFTCCFBECAQExCzAJBgUrDgMCGgUAMIIDxQYJKoZIhvcNAQcBoIIDtgSCA7IxggOuMAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAKIwDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGVKEVgKMOMBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEB47vKEZf/vsyhQ3HlCSXTQwHAIBBQIBAQQUjgGCbMez0pV8iXg2xvPSJHOA0ewwHgIBCAIBAQQWFhQyMDIwLTAyLTAzVDIzOjAxOjI1WjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
  public $transaction_id =>
  string(15) "530000529059913"
  public $request_type =>
  string(6) "mobile"
  public $notification_type =>
  NULL
  public $email =>
  string(22) "twinkiefan34@gmail.com"
  public $brand =>
  string(6) "drumeo"
  public $valid =>
  int(1)
  public $validation_error =>
  NULL
  public $payment_id =>
  NULL
  public $subscription_id =>
  int(73945)
  public $raw_receipt_response =>
  string(23518) "O:42:"ReceiptValidator\\iTunes\\ProductionResponse":13:{s:14:"\000*\000result_code";i:0;s:12:"\000*\000bundle_id";s:23:"com.drumeo.DrumeoMobile";s:14:"\000*\000app_item_id";i:1460388277;s:25:"\000*\000original_purchase_date";O:13:"Carbon\\Carbon":3:{s:4:"date";s:26:"2020-01-29 12:47:53.000000";s:13:"timezone_type";i:1;s:8:"timezone";s:6:"+00:00";}s:15:"\000*\000request_date";O:13:"Carbon\\Carbon":3:{s:4:"date";s:26:"2020-02-03 23:01:26.000000";s:13:"timezone_type";i:1;s:8:"timezone";s:6:"+00:00";}s:24:"\000"...
  public $created_at =>
  string(19) "2020-02-03 23:01:25"
  public $updated_at =>
  string(19) "2020-02-03 23:01:27"
}
/app/ecommerce/src/Commands/FixSerializeErrorInAppPurchaseTables.php:127:
class ReceiptValidator\iTunes\ProductionResponse#2236 (13) {
  protected $result_code =>
  int(0)
  protected $bundle_id =>
  string(23) "com.drumeo.DrumeoMobile"
  protected $app_item_id =>
  int(1460388277)
  protected $original_purchase_date =>
  class Carbon\Carbon#2827 (3) {
    public $date =>
    string(26) "2020-01-29 12:47:53.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $request_date =>
  class Carbon\Carbon#2845 (3) {
    public $date =>
    string(26) "2020-02-03 23:01:26.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $receipt_creation_date =>
  class Carbon\Carbon#2844 (3) {
    public $date =>
    string(26) "2020-02-03 23:01:25.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $receipt =>
  array(18) {
    'receipt_type' =>
    string(10) "Production"
    'adam_id' =>
    int(1460388277)
    'app_item_id' =>
    int(1460388277)
    'bundle_id' =>
    string(23) "com.drumeo.DrumeoMobile"
    'application_version' =>
    string(7) "0.0.245"
    'download_id' =>
    int(93050827219726)
    'version_external_identifier' =>
    int(834486669)
    'receipt_creation_date' =>
    string(27) "2020-02-03 23:01:25 Etc/GMT"
    'receipt_creation_date_ms' =>
    string(13) "1580770885000"
    'receipt_creation_date_pst' =>
    string(39) "2020-02-03 15:01:25 America/Los_Angeles"
    'request_date' =>
    string(27) "2020-02-03 23:01:26 Etc/GMT"
    'request_date_ms' =>
    string(13) "1580770886178"
    'request_date_pst' =>
    string(39) "2020-02-03 15:01:26 America/Los_Angeles"
    'original_purchase_date' =>
    string(27) "2020-01-29 12:47:53 Etc/GMT"
    'original_purchase_date_ms' =>
    string(13) "1580302073000"
    'original_purchase_date_pst' =>
    string(39) "2020-01-29 04:47:53 America/Los_Angeles"
    'original_application_version' =>
    string(7) "0.0.245"
    'in_app' =>
    array(1) {
      [0] =>
      array(16) {
        ...
      }
    }
  }
  protected $latest_receipt =>
  string(6888) "MIIUKAYJKoZIhvcNAQcCoIIUGTCCFBUCAQExCzAJBgUrDgMCGgUAMIIDyQYJKoZIhvcNAQcBoIIDugSCA7YxggOyMAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAKIwDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGVKEVgKMOMBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEB47vKEZf/vsyhQ3HlCSXTQwHAIBBQIBAQQUjgGCbMez0pV8iXg2xvPSJHOA0ewwHgIBCAIBAQQWFhQyMDIwLTAyLTAzVDIzOjAxOjI1WjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
  protected $latest_receipt_info =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PurchaseItem#2842 (13) {
      protected $quantity =>
      int(1)
      protected $product_id =>
      string(24) "drumeo_app_1_year_member"
      protected $web_order_line_item_id =>
      string(15) "530000188706295"
      protected $transaction_id =>
      string(15) "530000529059913"
      protected $original_transaction_id =>
      string(15) "530000529059913"
      protected $purchase_date =>
      class Carbon\Carbon#2831 (3) {
        ...
      }
      protected $original_purchase_date =>
      class Carbon\Carbon#2841 (3) {
        ...
      }
      protected $expires_date =>
      class Carbon\Carbon#2840 (3) {
        ...
      }
      protected $cancellation_date =>
      NULL
      protected $is_trial_period =>
      bool(true)
      protected $is_in_intro_offer_period =>
      bool(false)
      protected $promotional_offer_id =>
      NULL
      protected $raw_data =>
      array(17) {
        ...
      }
    }
  }
  protected $purchases =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PurchaseItem#2828 (13) {
      protected $quantity =>
      int(1)
      protected $product_id =>
      string(24) "drumeo_app_1_year_member"
      protected $web_order_line_item_id =>
      string(15) "530000188706295"
      protected $transaction_id =>
      string(15) "530000529059913"
      protected $original_transaction_id =>
      string(15) "530000529059913"
      protected $purchase_date =>
      class Carbon\Carbon#2832 (3) {
        ...
      }
      protected $original_purchase_date =>
      class Carbon\Carbon#2838 (3) {
        ...
      }
      protected $expires_date =>
      class Carbon\Carbon#2829 (3) {
        ...
      }
      protected $cancellation_date =>
      NULL
      protected $is_trial_period =>
      bool(true)
      protected $is_in_intro_offer_period =>
      bool(false)
      protected $promotional_offer_id =>
      NULL
      protected $raw_data =>
      array(16) {
        ...
      }
    }
  }
  protected $pending_renewal_info =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PendingRenewalInfo#2848 (7) {
      protected $product_id =>
      string(24) "drumeo_app_1_year_member"
      protected $auto_renew_product_id =>
      string(24) "drumeo_app_1_year_member"
      protected $original_transaction_id =>
      string(15) "530000529059913"
      protected $auto_renew_status =>
      bool(true)
      protected $expiration_intent =>
      NULL
      protected $is_in_billing_retry_period =>
      NULL
      protected $raw_data =>
      array(4) {
        ...
      }
    }
  }
  protected $raw_data =>
  array(6) {
    'status' =>
    int(0)
    'environment' =>
    string(10) "Production"
    'receipt' =>
    array(18) {
      'receipt_type' =>
      string(10) "Production"
      'adam_id' =>
      int(1460388277)
      'app_item_id' =>
      int(1460388277)
      'bundle_id' =>
      string(23) "com.drumeo.DrumeoMobile"
      'application_version' =>
      string(7) "0.0.245"
      'download_id' =>
      int(93050827219726)
      'version_external_identifier' =>
      int(834486669)
      'receipt_creation_date' =>
      string(27) "2020-02-03 23:01:25 Etc/GMT"
      'receipt_creation_date_ms' =>
      string(13) "1580770885000"
      'receipt_creation_date_pst' =>
      string(39) "2020-02-03 15:01:25 America/Los_Angeles"
      'request_date' =>
      string(27) "2020-02-03 23:01:26 Etc/GMT"
      'request_date_ms' =>
      string(13) "1580770886178"
      'request_date_pst' =>
      string(39) "2020-02-03 15:01:26 America/Los_Angeles"
      'original_purchase_date' =>
      string(27) "2020-01-29 12:47:53 Etc/GMT"
      'original_purchase_date_ms' =>
      string(13) "1580302073000"
      'original_purchase_date_pst' =>
      string(39) "2020-01-29 04:47:53 America/Los_Angeles"
      'original_application_version' =>
      string(7) "0.0.245"
      'in_app' =>
      array(1) {
        ...
      }
    }
    'latest_receipt_info' =>
    array(1) {
      [0] =>
      array(17) {
        ...
      }
    }
    'latest_receipt' =>
    string(6888) "MIIUKAYJKoZIhvcNAQcCoIIUGTCCFBUCAQExCzAJBgUrDgMCGgUAMIIDyQYJKoZIhvcNAQcBoIIDugSCA7YxggOyMAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAKIwDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGVKEVgKMOMBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEB47vKEZf/vsyhQ3HlCSXTQwHAIBBQIBAQQUjgGCbMez0pV8iXg2xvPSJHOA0ewwHgIBCAIBAQQWFhQyMDIwLTAyLTAzVDIzOjAxOjI1WjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
    'pending_renewal_info' =>
    array(1) {
      [0] =>
      array(4) {
        ...
      }
    }
  }
  protected $is_retryable =>
  bool(false)
}
---------------------------------------------------------
/app/ecommerce/src/Commands/FixSerializeErrorInAppPurchaseTables.php:126:
class stdClass#2805 (14) {
  public $id =>
  int(549)
  public $receipt =>
  string(6876) "MIIUIQYJKoZIhvcNAQcCoIIUEjCCFA4CAQExCzAJBgUrDgMCGgUAMIIDwgYJKoZIhvcNAQcBoIIDswSCA68xggOrMAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAKIwDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGZBW+dRpCMBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEMWJg4WzaBmg2CHPMNCgWZMwHAIBBQIBAQQUIn8Jb7i+gNPte8Lwa8jl1bk2UvwwHgIBCAIBAQQWFhQyMDIwLTAyLTAzVDIzOjI2OjQ3WjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
  public $transaction_id =>
  string(15) "700000447854675"
  public $request_type =>
  string(6) "mobile"
  public $notification_type =>
  NULL
  public $email =>
  string(20) "markvartok@gmail.com"
  public $brand =>
  string(6) "drumeo"
  public $valid =>
  int(1)
  public $validation_error =>
  NULL
  public $payment_id =>
  NULL
  public $subscription_id =>
  int(73946)
  public $raw_receipt_response =>
  string(23461) "O:42:"ReceiptValidator\\iTunes\\ProductionResponse":13:{s:14:"\000*\000result_code";i:0;s:12:"\000*\000bundle_id";s:23:"com.drumeo.DrumeoMobile";s:14:"\000*\000app_item_id";i:1460388277;s:25:"\000*\000original_purchase_date";O:13:"Carbon\\Carbon":3:{s:4:"date";s:26:"2020-02-03 23:25:07.000000";s:13:"timezone_type";i:1;s:8:"timezone";s:6:"+00:00";}s:15:"\000*\000request_date";O:13:"Carbon\\Carbon":3:{s:4:"date";s:26:"2020-02-03 23:26:49.000000";s:13:"timezone_type";i:1;s:8:"timezone";s:6:"+00:00";}s:24:"\000"...
  public $created_at =>
  string(19) "2020-02-03 23:26:48"
  public $updated_at =>
  string(19) "2020-02-03 23:26:49"
}
/app/ecommerce/src/Commands/FixSerializeErrorInAppPurchaseTables.php:127:
class ReceiptValidator\iTunes\ProductionResponse#2236 (13) {
  protected $result_code =>
  int(0)
  protected $bundle_id =>
  string(23) "com.drumeo.DrumeoMobile"
  protected $app_item_id =>
  int(1460388277)
  protected $original_purchase_date =>
  class Carbon\Carbon#2848 (3) {
    public $date =>
    string(26) "2020-02-03 23:25:07.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $request_date =>
  class Carbon\Carbon#2828 (3) {
    public $date =>
    string(26) "2020-02-03 23:26:49.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $receipt_creation_date =>
  class Carbon\Carbon#2829 (3) {
    public $date =>
    string(26) "2020-02-03 23:26:47.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $receipt =>
  array(18) {
    'receipt_type' =>
    string(10) "Production"
    'adam_id' =>
    int(1460388277)
    'app_item_id' =>
    int(1460388277)
    'bundle_id' =>
    string(23) "com.drumeo.DrumeoMobile"
    'application_version' =>
    string(7) "0.0.245"
    'download_id' =>
    int(110044552436290)
    'version_external_identifier' =>
    int(834486669)
    'receipt_creation_date' =>
    string(27) "2020-02-03 23:26:47 Etc/GMT"
    'receipt_creation_date_ms' =>
    string(13) "1580772407000"
    'receipt_creation_date_pst' =>
    string(39) "2020-02-03 15:26:47 America/Los_Angeles"
    'request_date' =>
    string(27) "2020-02-03 23:26:48 Etc/GMT"
    'request_date_ms' =>
    string(13) "1580772408861"
    'request_date_pst' =>
    string(39) "2020-02-03 15:26:48 America/Los_Angeles"
    'original_purchase_date' =>
    string(27) "2020-02-03 23:25:07 Etc/GMT"
    'original_purchase_date_ms' =>
    string(13) "1580772307000"
    'original_purchase_date_pst' =>
    string(39) "2020-02-03 15:25:07 America/Los_Angeles"
    'original_application_version' =>
    string(7) "0.0.245"
    'in_app' =>
    array(1) {
      [0] =>
      array(16) {
        ...
      }
    }
  }
  protected $latest_receipt =>
  string(6852) "MIIUDQYJKoZIhvcNAQcCoIIT/jCCE/oCAQExCzAJBgUrDgMCGgUAMIIDrgYJKoZIhvcNAQcBoIIDnwSCA5sxggOXMAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAKIwDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGZBW+dRpCMBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEMWJg4WzaBmg2CHPMNCgWZMwHAIBBQIBAQQUIn8Jb7i+gNPte8Lwa8jl1bk2UvwwHgIBCAIBAQQWFhQyMDIwLTAyLTAzVDIzOjI2OjQ3WjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
  protected $latest_receipt_info =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PurchaseItem#2838 (13) {
      protected $quantity =>
      int(1)
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $web_order_line_item_id =>
      string(15) "700000161192436"
      protected $transaction_id =>
      string(15) "700000447854675"
      protected $original_transaction_id =>
      string(15) "700000447854675"
      protected $purchase_date =>
      class Carbon\Carbon#2832 (3) {
        ...
      }
      protected $original_purchase_date =>
      class Carbon\Carbon#2842 (3) {
        ...
      }
      protected $expires_date =>
      class Carbon\Carbon#2840 (3) {
        ...
      }
      protected $cancellation_date =>
      NULL
      protected $is_trial_period =>
      bool(true)
      protected $is_in_intro_offer_period =>
      bool(false)
      protected $promotional_offer_id =>
      NULL
      protected $raw_data =>
      array(17) {
        ...
      }
    }
  }
  protected $purchases =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PurchaseItem#2841 (13) {
      protected $quantity =>
      int(1)
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $web_order_line_item_id =>
      string(15) "700000161192436"
      protected $transaction_id =>
      string(15) "700000447854675"
      protected $original_transaction_id =>
      string(15) "700000447854675"
      protected $purchase_date =>
      class Carbon\Carbon#2831 (3) {
        ...
      }
      protected $original_purchase_date =>
      class Carbon\Carbon#2844 (3) {
        ...
      }
      protected $expires_date =>
      class Carbon\Carbon#2845 (3) {
        ...
      }
      protected $cancellation_date =>
      NULL
      protected $is_trial_period =>
      bool(true)
      protected $is_in_intro_offer_period =>
      bool(false)
      protected $promotional_offer_id =>
      NULL
      protected $raw_data =>
      array(16) {
        ...
      }
    }
  }
  protected $pending_renewal_info =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PendingRenewalInfo#2827 (7) {
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $auto_renew_product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $original_transaction_id =>
      string(15) "700000447854675"
      protected $auto_renew_status =>
      bool(true)
      protected $expiration_intent =>
      NULL
      protected $is_in_billing_retry_period =>
      NULL
      protected $raw_data =>
      array(4) {
        ...
      }
    }
  }
  protected $raw_data =>
  array(6) {
    'status' =>
    int(0)
    'environment' =>
    string(10) "Production"
    'receipt' =>
    array(18) {
      'receipt_type' =>
      string(10) "Production"
      'adam_id' =>
      int(1460388277)
      'app_item_id' =>
      int(1460388277)
      'bundle_id' =>
      string(23) "com.drumeo.DrumeoMobile"
      'application_version' =>
      string(7) "0.0.245"
      'download_id' =>
      int(110044552436290)
      'version_external_identifier' =>
      int(834486669)
      'receipt_creation_date' =>
      string(27) "2020-02-03 23:26:47 Etc/GMT"
      'receipt_creation_date_ms' =>
      string(13) "1580772407000"
      'receipt_creation_date_pst' =>
      string(39) "2020-02-03 15:26:47 America/Los_Angeles"
      'request_date' =>
      string(27) "2020-02-03 23:26:48 Etc/GMT"
      'request_date_ms' =>
      string(13) "1580772408861"
      'request_date_pst' =>
      string(39) "2020-02-03 15:26:48 America/Los_Angeles"
      'original_purchase_date' =>
      string(27) "2020-02-03 23:25:07 Etc/GMT"
      'original_purchase_date_ms' =>
      string(13) "1580772307000"
      'original_purchase_date_pst' =>
      string(39) "2020-02-03 15:25:07 America/Los_Angeles"
      'original_application_version' =>
      string(7) "0.0.245"
      'in_app' =>
      array(1) {
        ...
      }
    }
    'latest_receipt_info' =>
    array(1) {
      [0] =>
      array(17) {
        ...
      }
    }
    'latest_receipt' =>
    string(6852) "MIIUDQYJKoZIhvcNAQcCoIIT/jCCE/oCAQExCzAJBgUrDgMCGgUAMIIDrgYJKoZIhvcNAQcBoIIDnwSCA5sxggOXMAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAKIwDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGZBW+dRpCMBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEMWJg4WzaBmg2CHPMNCgWZMwHAIBBQIBAQQUIn8Jb7i+gNPte8Lwa8jl1bk2UvwwHgIBCAIBAQQWFhQyMDIwLTAyLTAzVDIzOjI2OjQ3WjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
    'pending_renewal_info' =>
    array(1) {
      [0] =>
      array(4) {
        ...
      }
    }
  }
  protected $is_retryable =>
  bool(false)
}
---------------------------------------------------------
/app/ecommerce/src/Commands/FixSerializeErrorInAppPurchaseTables.php:126:
class stdClass#2806 (14) {
  public $id =>
  int(550)
  public $receipt =>
  string(6908) "MIIUOAYJKoZIhvcNAQcCoIIUKTCCFCUCAQExCzAJBgUrDgMCGgUAMIID2QYJKoZIhvcNAQcBoIIDygSCA8YxggPCMAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAM0wDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGZP9zt+KHMBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4xNzYwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEDKEfC4FvFRvgODSdP5llOwwHAIBBQIBAQQUGYw1I5MiViQguBqwQmiTdSOivRgwHgIBCAIBAQQWFhQyMDIwLTAyLTA0VDAxOjEyOjQ3WjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
  public $transaction_id =>
  string(15) "710000497709568"
  public $request_type =>
  string(6) "mobile"
  public $notification_type =>
  NULL
  public $email =>
  string(22) "nicolasbilodeau@me.com"
  public $brand =>
  string(6) "drumeo"
  public $valid =>
  int(1)
  public $validation_error =>
  NULL
  public $payment_id =>
  NULL
  public $subscription_id =>
  int(73947)
  public $raw_receipt_response =>
  string(23552) "O:42:"ReceiptValidator\\iTunes\\ProductionResponse":13:{s:14:"\000*\000result_code";i:0;s:12:"\000*\000bundle_id";s:23:"com.drumeo.DrumeoMobile";s:14:"\000*\000app_item_id";i:1460388277;s:25:"\000*\000original_purchase_date";O:13:"Carbon\\Carbon":3:{s:4:"date";s:26:"2020-01-04 17:17:44.000000";s:13:"timezone_type";i:1;s:8:"timezone";s:6:"+00:00";}s:15:"\000*\000request_date";O:13:"Carbon\\Carbon":3:{s:4:"date";s:26:"2020-02-04 01:12:49.000000";s:13:"timezone_type";i:1;s:8:"timezone";s:6:"+00:00";}s:24:"\000"...
  public $created_at =>
  string(19) "2020-02-04 01:12:47"
  public $updated_at =>
  string(19) "2020-02-04 01:12:49"
}
/app/ecommerce/src/Commands/FixSerializeErrorInAppPurchaseTables.php:127:
class ReceiptValidator\iTunes\ProductionResponse#2236 (13) {
  protected $result_code =>
  int(0)
  protected $bundle_id =>
  string(23) "com.drumeo.DrumeoMobile"
  protected $app_item_id =>
  int(1460388277)
  protected $original_purchase_date =>
  class Carbon\Carbon#2827 (3) {
    public $date =>
    string(26) "2020-01-04 17:17:44.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $request_date =>
  class Carbon\Carbon#2841 (3) {
    public $date =>
    string(26) "2020-02-04 01:12:49.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $receipt_creation_date =>
  class Carbon\Carbon#2845 (3) {
    public $date =>
    string(26) "2020-02-04 01:12:47.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $receipt =>
  array(18) {
    'receipt_type' =>
    string(10) "Production"
    'adam_id' =>
    int(1460388277)
    'app_item_id' =>
    int(1460388277)
    'bundle_id' =>
    string(23) "com.drumeo.DrumeoMobile"
    'application_version' =>
    string(7) "0.0.245"
    'download_id' =>
    int(111048320868999)
    'version_external_identifier' =>
    int(834486669)
    'receipt_creation_date' =>
    string(27) "2020-02-04 01:12:47 Etc/GMT"
    'receipt_creation_date_ms' =>
    string(13) "1580778767000"
    'receipt_creation_date_pst' =>
    string(39) "2020-02-03 17:12:47 America/Los_Angeles"
    'request_date' =>
    string(27) "2020-02-04 01:12:48 Etc/GMT"
    'request_date_ms' =>
    string(13) "1580778768522"
    'request_date_pst' =>
    string(39) "2020-02-03 17:12:48 America/Los_Angeles"
    'original_purchase_date' =>
    string(27) "2020-01-04 17:17:44 Etc/GMT"
    'original_purchase_date_ms' =>
    string(13) "1578158264000"
    'original_purchase_date_pst' =>
    string(39) "2020-01-04 09:17:44 America/Los_Angeles"
    'original_application_version' =>
    string(7) "0.0.176"
    'in_app' =>
    array(1) {
      [0] =>
      array(16) {
        ...
      }
    }
  }
  protected $latest_receipt =>
  string(6904) "MIIUNgYJKoZIhvcNAQcCoIIUJzCCFCMCAQExCzAJBgUrDgMCGgUAMIID1wYJKoZIhvcNAQcBoIIDyASCA8QxggPAMAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAM0wDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGZP9zt+KHMBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4xNzYwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEDKEfC4FvFRvgODSdP5llOwwHAIBBQIBAQQUGYw1I5MiViQguBqwQmiTdSOivRgwHgIBCAIBAQQWFhQyMDIwLTAyLTA0VDAxOjEyOjQ3WjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
  protected $latest_receipt_info =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PurchaseItem#2844 (13) {
      protected $quantity =>
      int(1)
      protected $product_id =>
      string(24) "drumeo_app_1_year_member"
      protected $web_order_line_item_id =>
      string(15) "710000178794691"
      protected $transaction_id =>
      string(15) "710000497709568"
      protected $original_transaction_id =>
      string(15) "710000497709568"
      protected $purchase_date =>
      class Carbon\Carbon#2831 (3) {
        ...
      }
      protected $original_purchase_date =>
      class Carbon\Carbon#2838 (3) {
        ...
      }
      protected $expires_date =>
      class Carbon\Carbon#2840 (3) {
        ...
      }
      protected $cancellation_date =>
      NULL
      protected $is_trial_period =>
      bool(true)
      protected $is_in_intro_offer_period =>
      bool(false)
      protected $promotional_offer_id =>
      NULL
      protected $raw_data =>
      array(17) {
        ...
      }
    }
  }
  protected $purchases =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PurchaseItem#2842 (13) {
      protected $quantity =>
      int(1)
      protected $product_id =>
      string(24) "drumeo_app_1_year_member"
      protected $web_order_line_item_id =>
      string(15) "710000178794691"
      protected $transaction_id =>
      string(15) "710000497709568"
      protected $original_transaction_id =>
      string(15) "710000497709568"
      protected $purchase_date =>
      class Carbon\Carbon#2832 (3) {
        ...
      }
      protected $original_purchase_date =>
      class Carbon\Carbon#2829 (3) {
        ...
      }
      protected $expires_date =>
      class Carbon\Carbon#2828 (3) {
        ...
      }
      protected $cancellation_date =>
      NULL
      protected $is_trial_period =>
      bool(true)
      protected $is_in_intro_offer_period =>
      bool(false)
      protected $promotional_offer_id =>
      NULL
      protected $raw_data =>
      array(16) {
        ...
      }
    }
  }
  protected $pending_renewal_info =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PendingRenewalInfo#2848 (7) {
      protected $product_id =>
      string(24) "drumeo_app_1_year_member"
      protected $auto_renew_product_id =>
      string(24) "drumeo_app_1_year_member"
      protected $original_transaction_id =>
      string(15) "710000497709568"
      protected $auto_renew_status =>
      bool(true)
      protected $expiration_intent =>
      NULL
      protected $is_in_billing_retry_period =>
      NULL
      protected $raw_data =>
      array(4) {
        ...
      }
    }
  }
  protected $raw_data =>
  array(6) {
    'status' =>
    int(0)
    'environment' =>
    string(10) "Production"
    'receipt' =>
    array(18) {
      'receipt_type' =>
      string(10) "Production"
      'adam_id' =>
      int(1460388277)
      'app_item_id' =>
      int(1460388277)
      'bundle_id' =>
      string(23) "com.drumeo.DrumeoMobile"
      'application_version' =>
      string(7) "0.0.245"
      'download_id' =>
      int(111048320868999)
      'version_external_identifier' =>
      int(834486669)
      'receipt_creation_date' =>
      string(27) "2020-02-04 01:12:47 Etc/GMT"
      'receipt_creation_date_ms' =>
      string(13) "1580778767000"
      'receipt_creation_date_pst' =>
      string(39) "2020-02-03 17:12:47 America/Los_Angeles"
      'request_date' =>
      string(27) "2020-02-04 01:12:48 Etc/GMT"
      'request_date_ms' =>
      string(13) "1580778768522"
      'request_date_pst' =>
      string(39) "2020-02-03 17:12:48 America/Los_Angeles"
      'original_purchase_date' =>
      string(27) "2020-01-04 17:17:44 Etc/GMT"
      'original_purchase_date_ms' =>
      string(13) "1578158264000"
      'original_purchase_date_pst' =>
      string(39) "2020-01-04 09:17:44 America/Los_Angeles"
      'original_application_version' =>
      string(7) "0.0.176"
      'in_app' =>
      array(1) {
        ...
      }
    }
    'latest_receipt_info' =>
    array(1) {
      [0] =>
      array(17) {
        ...
      }
    }
    'latest_receipt' =>
    string(6904) "MIIUNgYJKoZIhvcNAQcCoIIUJzCCFCMCAQExCzAJBgUrDgMCGgUAMIID1wYJKoZIhvcNAQcBoIIDyASCA8QxggPAMAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAM0wDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGZP9zt+KHMBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4xNzYwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEDKEfC4FvFRvgODSdP5llOwwHAIBBQIBAQQUGYw1I5MiViQguBqwQmiTdSOivRgwHgIBCAIBAQQWFhQyMDIwLTAyLTA0VDAxOjEyOjQ3WjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
    'pending_renewal_info' =>
    array(1) {
      [0] =>
      array(4) {
        ...
      }
    }
  }
  protected $is_retryable =>
  bool(false)
}
---------------------------------------------------------
/app/ecommerce/src/Commands/FixSerializeErrorInAppPurchaseTables.php:126:
class stdClass#2807 (14) {
  public $id =>
  int(551)
  public $receipt =>
  string(6908) "MIIUOQYJKoZIhvcNAQcCoIIUKjCCFCYCAQExCzAJBgUrDgMCGgUAMIID2gYJKoZIhvcNAQcBoIIDywSCA8cxggPDMAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAM8wDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGFPsErlRcMBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEOYO6cvBWk6US7TJ2y8eYjswHAIBBQIBAQQUgM0wJBCdiY2SHLnhUr8FA+BC8bIwHgIBCAIBAQQWFhQyMDIwLTAyLTA0VDAyOjI3OjU0WjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
  public $transaction_id =>
  string(14) "30000712131429"
  public $request_type =>
  string(6) "mobile"
  public $notification_type =>
  NULL
  public $email =>
  string(24) "brentanthony33@gmail.com"
  public $brand =>
  string(6) "drumeo"
  public $valid =>
  int(1)
  public $validation_error =>
  NULL
  public $payment_id =>
  NULL
  public $subscription_id =>
  int(73948)
  public $raw_receipt_response =>
  string(23451) "O:42:"ReceiptValidator\\iTunes\\ProductionResponse":13:{s:14:"\000*\000result_code";i:0;s:12:"\000*\000bundle_id";s:23:"com.drumeo.DrumeoMobile";s:14:"\000*\000app_item_id";i:1460388277;s:25:"\000*\000original_purchase_date";O:13:"Carbon\\Carbon":3:{s:4:"date";s:26:"2020-02-02 15:32:56.000000";s:13:"timezone_type";i:1;s:8:"timezone";s:6:"+00:00";}s:15:"\000*\000request_date";O:13:"Carbon\\Carbon":3:{s:4:"date";s:26:"2020-02-04 02:27:56.000000";s:13:"timezone_type";i:1;s:8:"timezone";s:6:"+00:00";}s:24:"\000"...
  public $created_at =>
  string(19) "2020-02-04 02:27:54"
  public $updated_at =>
  string(19) "2020-02-04 02:27:57"
}
/app/ecommerce/src/Commands/FixSerializeErrorInAppPurchaseTables.php:127:
class ReceiptValidator\iTunes\ProductionResponse#2236 (13) {
  protected $result_code =>
  int(0)
  protected $bundle_id =>
  string(23) "com.drumeo.DrumeoMobile"
  protected $app_item_id =>
  int(1460388277)
  protected $original_purchase_date =>
  class Carbon\Carbon#2848 (3) {
    public $date =>
    string(26) "2020-02-02 15:32:56.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $request_date =>
  class Carbon\Carbon#2842 (3) {
    public $date =>
    string(26) "2020-02-04 02:27:56.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $receipt_creation_date =>
  class Carbon\Carbon#2828 (3) {
    public $date =>
    string(26) "2020-02-04 02:27:54.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $receipt =>
  array(18) {
    'receipt_type' =>
    string(10) "Production"
    'adam_id' =>
    int(1460388277)
    'app_item_id' =>
    int(1460388277)
    'bundle_id' =>
    string(23) "com.drumeo.DrumeoMobile"
    'application_version' =>
    string(7) "0.0.245"
    'download_id' =>
    int(23068347880540)
    'version_external_identifier' =>
    int(834486669)
    'receipt_creation_date' =>
    string(27) "2020-02-04 02:27:54 Etc/GMT"
    'receipt_creation_date_ms' =>
    string(13) "1580783274000"
    'receipt_creation_date_pst' =>
    string(39) "2020-02-03 18:27:54 America/Los_Angeles"
    'request_date' =>
    string(27) "2020-02-04 02:27:56 Etc/GMT"
    'request_date_ms' =>
    string(13) "1580783276207"
    'request_date_pst' =>
    string(39) "2020-02-03 18:27:56 America/Los_Angeles"
    'original_purchase_date' =>
    string(27) "2020-02-02 15:32:56 Etc/GMT"
    'original_purchase_date_ms' =>
    string(13) "1580657576000"
    'original_purchase_date_pst' =>
    string(39) "2020-02-02 07:32:56 America/Los_Angeles"
    'original_application_version' =>
    string(7) "0.0.245"
    'in_app' =>
    array(1) {
      [0] =>
      array(16) {
        ...
      }
    }
  }
  protected $latest_receipt =>
  string(6860) "MIIUEwYJKoZIhvcNAQcCoIIUBDCCFAACAQExCzAJBgUrDgMCGgUAMIIDtAYJKoZIhvcNAQcBoIIDpQSCA6ExggOdMAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAM8wDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGFPsErlRcMBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEOYO6cvBWk6US7TJ2y8eYjswHAIBBQIBAQQUgM0wJBCdiY2SHLnhUr8FA+BC8bIwHgIBCAIBAQQWFhQyMDIwLTAyLTA0VDAyOjI3OjU0WjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
  protected $latest_receipt_info =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PurchaseItem#2829 (13) {
      protected $quantity =>
      int(1)
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $web_order_line_item_id =>
      string(14) "30000248422107"
      protected $transaction_id =>
      string(14) "30000712131429"
      protected $original_transaction_id =>
      string(14) "30000712131429"
      protected $purchase_date =>
      class Carbon\Carbon#2832 (3) {
        ...
      }
      protected $original_purchase_date =>
      class Carbon\Carbon#2844 (3) {
        ...
      }
      protected $expires_date =>
      class Carbon\Carbon#2840 (3) {
        ...
      }
      protected $cancellation_date =>
      NULL
      protected $is_trial_period =>
      bool(true)
      protected $is_in_intro_offer_period =>
      bool(false)
      protected $promotional_offer_id =>
      NULL
      protected $raw_data =>
      array(17) {
        ...
      }
    }
  }
  protected $purchases =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PurchaseItem#2838 (13) {
      protected $quantity =>
      int(1)
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $web_order_line_item_id =>
      string(14) "30000248422107"
      protected $transaction_id =>
      string(14) "30000712131429"
      protected $original_transaction_id =>
      string(14) "30000712131429"
      protected $purchase_date =>
      class Carbon\Carbon#2831 (3) {
        ...
      }
      protected $original_purchase_date =>
      class Carbon\Carbon#2845 (3) {
        ...
      }
      protected $expires_date =>
      class Carbon\Carbon#2841 (3) {
        ...
      }
      protected $cancellation_date =>
      NULL
      protected $is_trial_period =>
      bool(true)
      protected $is_in_intro_offer_period =>
      bool(false)
      protected $promotional_offer_id =>
      NULL
      protected $raw_data =>
      array(16) {
        ...
      }
    }
  }
  protected $pending_renewal_info =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PendingRenewalInfo#2827 (7) {
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $auto_renew_product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $original_transaction_id =>
      string(14) "30000712131429"
      protected $auto_renew_status =>
      bool(true)
      protected $expiration_intent =>
      NULL
      protected $is_in_billing_retry_period =>
      NULL
      protected $raw_data =>
      array(4) {
        ...
      }
    }
  }
  protected $raw_data =>
  array(6) {
    'status' =>
    int(0)
    'environment' =>
    string(10) "Production"
    'receipt' =>
    array(18) {
      'receipt_type' =>
      string(10) "Production"
      'adam_id' =>
      int(1460388277)
      'app_item_id' =>
      int(1460388277)
      'bundle_id' =>
      string(23) "com.drumeo.DrumeoMobile"
      'application_version' =>
      string(7) "0.0.245"
      'download_id' =>
      int(23068347880540)
      'version_external_identifier' =>
      int(834486669)
      'receipt_creation_date' =>
      string(27) "2020-02-04 02:27:54 Etc/GMT"
      'receipt_creation_date_ms' =>
      string(13) "1580783274000"
      'receipt_creation_date_pst' =>
      string(39) "2020-02-03 18:27:54 America/Los_Angeles"
      'request_date' =>
      string(27) "2020-02-04 02:27:56 Etc/GMT"
      'request_date_ms' =>
      string(13) "1580783276207"
      'request_date_pst' =>
      string(39) "2020-02-03 18:27:56 America/Los_Angeles"
      'original_purchase_date' =>
      string(27) "2020-02-02 15:32:56 Etc/GMT"
      'original_purchase_date_ms' =>
      string(13) "1580657576000"
      'original_purchase_date_pst' =>
      string(39) "2020-02-02 07:32:56 America/Los_Angeles"
      'original_application_version' =>
      string(7) "0.0.245"
      'in_app' =>
      array(1) {
        ...
      }
    }
    'latest_receipt_info' =>
    array(1) {
      [0] =>
      array(17) {
        ...
      }
    }
    'latest_receipt' =>
    string(6860) "MIIUEwYJKoZIhvcNAQcCoIIUBDCCFAACAQExCzAJBgUrDgMCGgUAMIIDtAYJKoZIhvcNAQcBoIIDpQSCA6ExggOdMAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAM8wDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGFPsErlRcMBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEOYO6cvBWk6US7TJ2y8eYjswHAIBBQIBAQQUgM0wJBCdiY2SHLnhUr8FA+BC8bIwHgIBCAIBAQQWFhQyMDIwLTAyLTA0VDAyOjI3OjU0WjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
    'pending_renewal_info' =>
    array(1) {
      [0] =>
      array(4) {
        ...
      }
    }
  }
  protected $is_retryable =>
  bool(false)
}
---------------------------------------------------------
/app/ecommerce/src/Commands/FixSerializeErrorInAppPurchaseTables.php:126:
class stdClass#2808 (14) {
  public $id =>
  int(552)
  public $receipt =>
  string(6864) "MIIUGAYJKoZIhvcNAQcCoIIUCTCCFAUCAQExCzAJBgUrDgMCGgUAMIIDuQYJKoZIhvcNAQcBoIIDqgSCA6YxggOiMAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAKEwDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGQYpml/KdMBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEFCd0hBF31kKDfcMlDkc3qwwHAIBBQIBAQQUzra9vUkx9gjSEZCaldvShctMbN4wHgIBCAIBAQQWFhQyMDIwLTAyLTA0VDA1OjAzOjIxWjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
  public $transaction_id =>
  string(15) "320000641108962"
  public $request_type =>
  string(6) "mobile"
  public $notification_type =>
  NULL
  public $email =>
  string(20) "lovedrones@gmail.com"
  public $brand =>
  string(6) "drumeo"
  public $valid =>
  int(1)
  public $validation_error =>
  NULL
  public $payment_id =>
  NULL
  public $subscription_id =>
  int(73956)
  public $raw_receipt_response =>
  string(23467) "O:42:"ReceiptValidator\\iTunes\\ProductionResponse":13:{s:14:"\000*\000result_code";i:0;s:12:"\000*\000bundle_id";s:23:"com.drumeo.DrumeoMobile";s:14:"\000*\000app_item_id";i:1460388277;s:25:"\000*\000original_purchase_date";O:13:"Carbon\\Carbon":3:{s:4:"date";s:26:"2020-02-04 01:29:19.000000";s:13:"timezone_type";i:1;s:8:"timezone";s:6:"+00:00";}s:15:"\000*\000request_date";O:13:"Carbon\\Carbon":3:{s:4:"date";s:26:"2020-02-04 05:03:23.000000";s:13:"timezone_type";i:1;s:8:"timezone";s:6:"+00:00";}s:24:"\000"...
  public $created_at =>
  string(19) "2020-02-04 05:03:22"
  public $updated_at =>
  string(19) "2020-02-04 05:03:23"
}
/app/ecommerce/src/Commands/FixSerializeErrorInAppPurchaseTables.php:127:
class ReceiptValidator\iTunes\ProductionResponse#2236 (13) {
  protected $result_code =>
  int(0)
  protected $bundle_id =>
  string(23) "com.drumeo.DrumeoMobile"
  protected $app_item_id =>
  int(1460388277)
  protected $original_purchase_date =>
  class Carbon\Carbon#2827 (3) {
    public $date =>
    string(26) "2020-02-04 01:29:19.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $request_date =>
  class Carbon\Carbon#2838 (3) {
    public $date =>
    string(26) "2020-02-04 05:03:23.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $receipt_creation_date =>
  class Carbon\Carbon#2841 (3) {
    public $date =>
    string(26) "2020-02-04 05:03:21.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $receipt =>
  array(18) {
    'receipt_type' =>
    string(10) "Production"
    'adam_id' =>
    int(1460388277)
    'app_item_id' =>
    int(1460388277)
    'bundle_id' =>
    string(23) "com.drumeo.DrumeoMobile"
    'application_version' =>
    string(7) "0.0.245"
    'download_id' =>
    int(72062682526365)
    'version_external_identifier' =>
    int(834486669)
    'receipt_creation_date' =>
    string(27) "2020-02-04 05:03:21 Etc/GMT"
    'receipt_creation_date_ms' =>
    string(13) "1580792601000"
    'receipt_creation_date_pst' =>
    string(39) "2020-02-03 21:03:21 America/Los_Angeles"
    'request_date' =>
    string(27) "2020-02-04 05:03:22 Etc/GMT"
    'request_date_ms' =>
    string(13) "1580792602942"
    'request_date_pst' =>
    string(39) "2020-02-03 21:03:22 America/Los_Angeles"
    'original_purchase_date' =>
    string(27) "2020-02-04 01:29:19 Etc/GMT"
    'original_purchase_date_ms' =>
    string(13) "1580779759000"
    'original_purchase_date_pst' =>
    string(39) "2020-02-03 17:29:19 America/Los_Angeles"
    'original_application_version' =>
    string(7) "0.0.245"
    'in_app' =>
    array(1) {
      [0] =>
      array(16) {
        ...
      }
    }
  }
  protected $latest_receipt =>
  string(6856) "MIIUEQYJKoZIhvcNAQcCoIIUAjCCE/4CAQExCzAJBgUrDgMCGgUAMIIDsgYJKoZIhvcNAQcBoIIDowSCA58xggObMAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAKEwDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGQYpml/KdMBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEFCd0hBF31kKDfcMlDkc3qwwHAIBBQIBAQQUzra9vUkx9gjSEZCaldvShctMbN4wHgIBCAIBAQQWFhQyMDIwLTAyLTA0VDA1OjAzOjIxWjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
  protected $latest_receipt_info =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PurchaseItem#2845 (13) {
      protected $quantity =>
      int(1)
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $web_order_line_item_id =>
      string(15) "320000228567396"
      protected $transaction_id =>
      string(15) "320000641108962"
      protected $original_transaction_id =>
      string(15) "320000641108962"
      protected $purchase_date =>
      class Carbon\Carbon#2831 (3) {
        ...
      }
      protected $original_purchase_date =>
      class Carbon\Carbon#2829 (3) {
        ...
      }
      protected $expires_date =>
      class Carbon\Carbon#2840 (3) {
        ...
      }
      protected $cancellation_date =>
      NULL
      protected $is_trial_period =>
      bool(true)
      protected $is_in_intro_offer_period =>
      bool(false)
      protected $promotional_offer_id =>
      NULL
      protected $raw_data =>
      array(17) {
        ...
      }
    }
  }
  protected $purchases =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PurchaseItem#2844 (13) {
      protected $quantity =>
      int(1)
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $web_order_line_item_id =>
      string(15) "320000228567396"
      protected $transaction_id =>
      string(15) "320000641108962"
      protected $original_transaction_id =>
      string(15) "320000641108962"
      protected $purchase_date =>
      class Carbon\Carbon#2832 (3) {
        ...
      }
      protected $original_purchase_date =>
      class Carbon\Carbon#2828 (3) {
        ...
      }
      protected $expires_date =>
      class Carbon\Carbon#2842 (3) {
        ...
      }
      protected $cancellation_date =>
      NULL
      protected $is_trial_period =>
      bool(true)
      protected $is_in_intro_offer_period =>
      bool(false)
      protected $promotional_offer_id =>
      NULL
      protected $raw_data =>
      array(16) {
        ...
      }
    }
  }
  protected $pending_renewal_info =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PendingRenewalInfo#2848 (7) {
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $auto_renew_product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $original_transaction_id =>
      string(15) "320000641108962"
      protected $auto_renew_status =>
      bool(true)
      protected $expiration_intent =>
      NULL
      protected $is_in_billing_retry_period =>
      NULL
      protected $raw_data =>
      array(4) {
        ...
      }
    }
  }
  protected $raw_data =>
  array(6) {
    'status' =>
    int(0)
    'environment' =>
    string(10) "Production"
    'receipt' =>
    array(18) {
      'receipt_type' =>
      string(10) "Production"
      'adam_id' =>
      int(1460388277)
      'app_item_id' =>
      int(1460388277)
      'bundle_id' =>
      string(23) "com.drumeo.DrumeoMobile"
      'application_version' =>
      string(7) "0.0.245"
      'download_id' =>
      int(72062682526365)
      'version_external_identifier' =>
      int(834486669)
      'receipt_creation_date' =>
      string(27) "2020-02-04 05:03:21 Etc/GMT"
      'receipt_creation_date_ms' =>
      string(13) "1580792601000"
      'receipt_creation_date_pst' =>
      string(39) "2020-02-03 21:03:21 America/Los_Angeles"
      'request_date' =>
      string(27) "2020-02-04 05:03:22 Etc/GMT"
      'request_date_ms' =>
      string(13) "1580792602942"
      'request_date_pst' =>
      string(39) "2020-02-03 21:03:22 America/Los_Angeles"
      'original_purchase_date' =>
      string(27) "2020-02-04 01:29:19 Etc/GMT"
      'original_purchase_date_ms' =>
      string(13) "1580779759000"
      'original_purchase_date_pst' =>
      string(39) "2020-02-03 17:29:19 America/Los_Angeles"
      'original_application_version' =>
      string(7) "0.0.245"
      'in_app' =>
      array(1) {
        ...
      }
    }
    'latest_receipt_info' =>
    array(1) {
      [0] =>
      array(17) {
        ...
      }
    }
    'latest_receipt' =>
    string(6856) "MIIUEQYJKoZIhvcNAQcCoIIUAjCCE/4CAQExCzAJBgUrDgMCGgUAMIIDsgYJKoZIhvcNAQcBoIIDowSCA58xggObMAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAKEwDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGQYpml/KdMBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEFCd0hBF31kKDfcMlDkc3qwwHAIBBQIBAQQUzra9vUkx9gjSEZCaldvShctMbN4wHgIBCAIBAQQWFhQyMDIwLTAyLTA0VDA1OjAzOjIxWjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
    'pending_renewal_info' =>
    array(1) {
      [0] =>
      array(4) {
        ...
      }
    }
  }
  protected $is_retryable =>
  bool(false)
}
---------------------------------------------------------
/app/ecommerce/src/Commands/FixSerializeErrorInAppPurchaseTables.php:126:
class stdClass#2809 (14) {
  public $id =>
  int(553)
  public $receipt =>
  string(6876) "MIIUIAYJKoZIhvcNAQcCoIIUETCCFA0CAQExCzAJBgUrDgMCGgUAMIIDwQYJKoZIhvcNAQcBoIIDsgSCA64xggOqMAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAL0wDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGP7Z7ans/MBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEAZSVzbhPoDn0k4xl8kjEbQwHAIBBQIBAQQUAq78Ct9VNp2/m0mZ8XsfJRyTgTEwHgIBCAIBAQQWFhQyMDIwLTAyLTA0VDExOjM4OjQyWjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
  public $transaction_id =>
  string(15) "300000544406019"
  public $request_type =>
  string(6) "mobile"
  public $notification_type =>
  NULL
  public $email =>
  string(19) "tcsprabhu@gmail.com"
  public $brand =>
  string(6) "drumeo"
  public $valid =>
  int(1)
  public $validation_error =>
  NULL
  public $payment_id =>
  NULL
  public $subscription_id =>
  int(73963)
  public $raw_receipt_response =>
  string(23491) "O:42:"ReceiptValidator\\iTunes\\ProductionResponse":13:{s:14:"\000*\000result_code";i:0;s:12:"\000*\000bundle_id";s:23:"com.drumeo.DrumeoMobile";s:14:"\000*\000app_item_id";i:1460388277;s:25:"\000*\000original_purchase_date";O:13:"Carbon\\Carbon":3:{s:4:"date";s:26:"2020-02-04 11:35:51.000000";s:13:"timezone_type";i:1;s:8:"timezone";s:6:"+00:00";}s:15:"\000*\000request_date";O:13:"Carbon\\Carbon":3:{s:4:"date";s:26:"2020-02-04 11:38:44.000000";s:13:"timezone_type";i:1;s:8:"timezone";s:6:"+00:00";}s:24:"\000"...
  public $created_at =>
  string(19) "2020-02-04 11:38:43"
  public $updated_at =>
  string(19) "2020-02-04 11:38:44"
}
/app/ecommerce/src/Commands/FixSerializeErrorInAppPurchaseTables.php:127:
class ReceiptValidator\iTunes\ProductionResponse#2236 (13) {
  protected $result_code =>
  int(0)
  protected $bundle_id =>
  string(23) "com.drumeo.DrumeoMobile"
  protected $app_item_id =>
  int(1460388277)
  protected $original_purchase_date =>
  class Carbon\Carbon#2848 (3) {
    public $date =>
    string(26) "2020-02-04 11:35:51.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $request_date =>
  class Carbon\Carbon#2844 (3) {
    public $date =>
    string(26) "2020-02-04 11:38:44.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $receipt_creation_date =>
  class Carbon\Carbon#2842 (3) {
    public $date =>
    string(26) "2020-02-04 11:38:42.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $receipt =>
  array(18) {
    'receipt_type' =>
    string(10) "Production"
    'adam_id' =>
    int(1460388277)
    'app_item_id' =>
    int(1460388277)
    'bundle_id' =>
    string(23) "com.drumeo.DrumeoMobile"
    'application_version' =>
    string(7) "0.0.245"
    'download_id' =>
    int(70052987173695)
    'version_external_identifier' =>
    int(834486669)
    'receipt_creation_date' =>
    string(27) "2020-02-04 11:38:42 Etc/GMT"
    'receipt_creation_date_ms' =>
    string(13) "1580816322000"
    'receipt_creation_date_pst' =>
    string(39) "2020-02-04 03:38:42 America/Los_Angeles"
    'request_date' =>
    string(27) "2020-02-04 11:38:43 Etc/GMT"
    'request_date_ms' =>
    string(13) "1580816323962"
    'request_date_pst' =>
    string(39) "2020-02-04 03:38:43 America/Los_Angeles"
    'original_purchase_date' =>
    string(27) "2020-02-04 11:35:51 Etc/GMT"
    'original_purchase_date_ms' =>
    string(13) "1580816151000"
    'original_purchase_date_pst' =>
    string(39) "2020-02-04 03:35:51 America/Los_Angeles"
    'original_application_version' =>
    string(7) "0.0.245"
    'in_app' =>
    array(1) {
      [0] =>
      array(16) {
        ...
      }
    }
  }
  protected $latest_receipt =>
  string(6868) "MIIUGQYJKoZIhvcNAQcCoIIUCjCCFAYCAQExCzAJBgUrDgMCGgUAMIIDugYJKoZIhvcNAQcBoIIDqwSCA6cxggOjMAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAL0wDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGP7Z7ans/MBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEAZSVzbhPoDn0k4xl8kjEbQwHAIBBQIBAQQUAq78Ct9VNp2/m0mZ8XsfJRyTgTEwHgIBCAIBAQQWFhQyMDIwLTAyLTA0VDExOjM4OjQyWjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
  protected $latest_receipt_info =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PurchaseItem#2828 (13) {
      protected $quantity =>
      int(1)
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $web_order_line_item_id =>
      string(15) "300000198009201"
      protected $transaction_id =>
      string(15) "300000544406019"
      protected $original_transaction_id =>
      string(15) "300000544406019"
      protected $purchase_date =>
      class Carbon\Carbon#2832 (3) {
        ...
      }
      protected $original_purchase_date =>
      class Carbon\Carbon#2845 (3) {
        ...
      }
      protected $expires_date =>
      class Carbon\Carbon#2840 (3) {
        ...
      }
      protected $cancellation_date =>
      NULL
      protected $is_trial_period =>
      bool(true)
      protected $is_in_intro_offer_period =>
      bool(false)
      protected $promotional_offer_id =>
      NULL
      protected $raw_data =>
      array(17) {
        ...
      }
    }
  }
  protected $purchases =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PurchaseItem#2829 (13) {
      protected $quantity =>
      int(1)
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $web_order_line_item_id =>
      string(15) "300000198009201"
      protected $transaction_id =>
      string(15) "300000544406019"
      protected $original_transaction_id =>
      string(15) "300000544406019"
      protected $purchase_date =>
      class Carbon\Carbon#2831 (3) {
        ...
      }
      protected $original_purchase_date =>
      class Carbon\Carbon#2841 (3) {
        ...
      }
      protected $expires_date =>
      class Carbon\Carbon#2838 (3) {
        ...
      }
      protected $cancellation_date =>
      NULL
      protected $is_trial_period =>
      bool(true)
      protected $is_in_intro_offer_period =>
      bool(false)
      protected $promotional_offer_id =>
      NULL
      protected $raw_data =>
      array(16) {
        ...
      }
    }
  }
  protected $pending_renewal_info =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PendingRenewalInfo#2827 (7) {
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $auto_renew_product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $original_transaction_id =>
      string(15) "300000544406019"
      protected $auto_renew_status =>
      bool(true)
      protected $expiration_intent =>
      NULL
      protected $is_in_billing_retry_period =>
      NULL
      protected $raw_data =>
      array(4) {
        ...
      }
    }
  }
  protected $raw_data =>
  array(6) {
    'status' =>
    int(0)
    'environment' =>
    string(10) "Production"
    'receipt' =>
    array(18) {
      'receipt_type' =>
      string(10) "Production"
      'adam_id' =>
      int(1460388277)
      'app_item_id' =>
      int(1460388277)
      'bundle_id' =>
      string(23) "com.drumeo.DrumeoMobile"
      'application_version' =>
      string(7) "0.0.245"
      'download_id' =>
      int(70052987173695)
      'version_external_identifier' =>
      int(834486669)
      'receipt_creation_date' =>
      string(27) "2020-02-04 11:38:42 Etc/GMT"
      'receipt_creation_date_ms' =>
      string(13) "1580816322000"
      'receipt_creation_date_pst' =>
      string(39) "2020-02-04 03:38:42 America/Los_Angeles"
      'request_date' =>
      string(27) "2020-02-04 11:38:43 Etc/GMT"
      'request_date_ms' =>
      string(13) "1580816323962"
      'request_date_pst' =>
      string(39) "2020-02-04 03:38:43 America/Los_Angeles"
      'original_purchase_date' =>
      string(27) "2020-02-04 11:35:51 Etc/GMT"
      'original_purchase_date_ms' =>
      string(13) "1580816151000"
      'original_purchase_date_pst' =>
      string(39) "2020-02-04 03:35:51 America/Los_Angeles"
      'original_application_version' =>
      string(7) "0.0.245"
      'in_app' =>
      array(1) {
        ...
      }
    }
    'latest_receipt_info' =>
    array(1) {
      [0] =>
      array(17) {
        ...
      }
    }
    'latest_receipt' =>
    string(6868) "MIIUGQYJKoZIhvcNAQcCoIIUCjCCFAYCAQExCzAJBgUrDgMCGgUAMIIDugYJKoZIhvcNAQcBoIIDqwSCA6cxggOjMAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAL0wDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGP7Z7ans/MBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEAZSVzbhPoDn0k4xl8kjEbQwHAIBBQIBAQQUAq78Ct9VNp2/m0mZ8XsfJRyTgTEwHgIBCAIBAQQWFhQyMDIwLTAyLTA0VDExOjM4OjQyWjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
    'pending_renewal_info' =>
    array(1) {
      [0] =>
      array(4) {
        ...
      }
    }
  }
  protected $is_retryable =>
  bool(false)
}
---------------------------------------------------------
/app/ecommerce/src/Commands/FixSerializeErrorInAppPurchaseTables.php:126:
class stdClass#2810 (14) {
  public $id =>
  int(554)
  public $receipt =>
  string(6932) "MIIUSwYJKoZIhvcNAQcCoIIUPDCCFDgCAQExCzAJBgUrDgMCGgUAMIID7AYJKoZIhvcNAQcBoIID3QSCA9kxggPVMAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAKIwDQIBDQIBAQQFAgMB/DYwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGPBYTVAvUMBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEAEVjgNiw/syJYPfTmdqPiIwHAIBBQIBAQQUbC6Yk3D7xHaQDZ70DziXkyKS9dEwHgIBCAIBAQQWFhQyMDIwLTAyLTA0VDE0OjQxOjI0WjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
  public $transaction_id =>
  string(15) "260000661338940"
  public $request_type =>
  string(6) "mobile"
  public $notification_type =>
  NULL
  public $email =>
  string(25) "bernardocassina@gmail.com"
  public $brand =>
  string(6) "drumeo"
  public $valid =>
  int(1)
  public $validation_error =>
  NULL
  public $payment_id =>
  NULL
  public $subscription_id =>
  int(73967)
  public $raw_receipt_response =>
  string(23558) "O:42:"ReceiptValidator\\iTunes\\ProductionResponse":13:{s:14:"\000*\000result_code";i:0;s:12:"\000*\000bundle_id";s:23:"com.drumeo.DrumeoMobile";s:14:"\000*\000app_item_id";i:1460388277;s:25:"\000*\000original_purchase_date";O:13:"Carbon\\Carbon":3:{s:4:"date";s:26:"2020-02-04 14:38:14.000000";s:13:"timezone_type";i:1;s:8:"timezone";s:6:"+00:00";}s:15:"\000*\000request_date";O:13:"Carbon\\Carbon":3:{s:4:"date";s:26:"2020-02-04 14:41:27.000000";s:13:"timezone_type";i:1;s:8:"timezone";s:6:"+00:00";}s:24:"\000"...
  public $created_at =>
  string(19) "2020-02-04 14:41:26"
  public $updated_at =>
  string(19) "2020-02-04 14:41:27"
}
/app/ecommerce/src/Commands/FixSerializeErrorInAppPurchaseTables.php:127:
class ReceiptValidator\iTunes\ProductionResponse#2236 (13) {
  protected $result_code =>
  int(0)
  protected $bundle_id =>
  string(23) "com.drumeo.DrumeoMobile"
  protected $app_item_id =>
  int(1460388277)
  protected $original_purchase_date =>
  class Carbon\Carbon#2827 (3) {
    public $date =>
    string(26) "2020-02-04 14:38:14.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $request_date =>
  class Carbon\Carbon#2829 (3) {
    public $date =>
    string(26) "2020-02-04 14:41:27.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $receipt_creation_date =>
  class Carbon\Carbon#2838 (3) {
    public $date =>
    string(26) "2020-02-04 14:41:24.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $receipt =>
  array(18) {
    'receipt_type' =>
    string(10) "Production"
    'adam_id' =>
    int(1460388277)
    'app_item_id' =>
    int(1460388277)
    'bundle_id' =>
    string(23) "com.drumeo.DrumeoMobile"
    'application_version' =>
    string(7) "0.0.245"
    'download_id' =>
    int(66065511222228)
    'version_external_identifier' =>
    int(834486669)
    'receipt_creation_date' =>
    string(27) "2020-02-04 14:41:24 Etc/GMT"
    'receipt_creation_date_ms' =>
    string(13) "1580827284000"
    'receipt_creation_date_pst' =>
    string(39) "2020-02-04 06:41:24 America/Los_Angeles"
    'request_date' =>
    string(27) "2020-02-04 14:41:26 Etc/GMT"
    'request_date_ms' =>
    string(13) "1580827286809"
    'request_date_pst' =>
    string(39) "2020-02-04 06:41:26 America/Los_Angeles"
    'original_purchase_date' =>
    string(27) "2020-02-04 14:38:14 Etc/GMT"
    'original_purchase_date_ms' =>
    string(13) "1580827094000"
    'original_purchase_date_pst' =>
    string(39) "2020-02-04 06:38:14 America/Los_Angeles"
    'original_application_version' =>
    string(7) "0.0.245"
    'in_app' =>
    array(1) {
      [0] =>
      array(16) {
        ...
      }
    }
  }
  protected $latest_receipt =>
  string(6908) "MIIUOQYJKoZIhvcNAQcCoIIUKjCCFCYCAQExCzAJBgUrDgMCGgUAMIID2gYJKoZIhvcNAQcBoIIDywSCA8cxggPDMAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAKIwDQIBDQIBAQQFAgMB/DYwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGPBYTVAvUMBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEAEVjgNiw/syJYPfTmdqPiIwHAIBBQIBAQQUbC6Yk3D7xHaQDZ70DziXkyKS9dEwHgIBCAIBAQQWFhQyMDIwLTAyLTA0VDE0OjQxOjI0WjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
  protected $latest_receipt_info =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PurchaseItem#2841 (13) {
      protected $quantity =>
      int(1)
      protected $product_id =>
      string(24) "drumeo_app_1_year_member"
      protected $web_order_line_item_id =>
      string(15) "260000238753404"
      protected $transaction_id =>
      string(15) "260000661338940"
      protected $original_transaction_id =>
      string(15) "260000661338940"
      protected $purchase_date =>
      class Carbon\Carbon#2831 (3) {
        ...
      }
      protected $original_purchase_date =>
      class Carbon\Carbon#2828 (3) {
        ...
      }
      protected $expires_date =>
      class Carbon\Carbon#2840 (3) {
        ...
      }
      protected $cancellation_date =>
      NULL
      protected $is_trial_period =>
      bool(true)
      protected $is_in_intro_offer_period =>
      bool(false)
      protected $promotional_offer_id =>
      NULL
      protected $raw_data =>
      array(17) {
        ...
      }
    }
  }
  protected $purchases =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PurchaseItem#2845 (13) {
      protected $quantity =>
      int(1)
      protected $product_id =>
      string(24) "drumeo_app_1_year_member"
      protected $web_order_line_item_id =>
      string(15) "260000238753404"
      protected $transaction_id =>
      string(15) "260000661338940"
      protected $original_transaction_id =>
      string(15) "260000661338940"
      protected $purchase_date =>
      class Carbon\Carbon#2832 (3) {
        ...
      }
      protected $original_purchase_date =>
      class Carbon\Carbon#2842 (3) {
        ...
      }
      protected $expires_date =>
      class Carbon\Carbon#2844 (3) {
        ...
      }
      protected $cancellation_date =>
      NULL
      protected $is_trial_period =>
      bool(true)
      protected $is_in_intro_offer_period =>
      bool(false)
      protected $promotional_offer_id =>
      NULL
      protected $raw_data =>
      array(16) {
        ...
      }
    }
  }
  protected $pending_renewal_info =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PendingRenewalInfo#2848 (7) {
      protected $product_id =>
      string(24) "drumeo_app_1_year_member"
      protected $auto_renew_product_id =>
      string(24) "drumeo_app_1_year_member"
      protected $original_transaction_id =>
      string(15) "260000661338940"
      protected $auto_renew_status =>
      bool(true)
      protected $expiration_intent =>
      NULL
      protected $is_in_billing_retry_period =>
      NULL
      protected $raw_data =>
      array(4) {
        ...
      }
    }
  }
  protected $raw_data =>
  array(6) {
    'status' =>
    int(0)
    'environment' =>
    string(10) "Production"
    'receipt' =>
    array(18) {
      'receipt_type' =>
      string(10) "Production"
      'adam_id' =>
      int(1460388277)
      'app_item_id' =>
      int(1460388277)
      'bundle_id' =>
      string(23) "com.drumeo.DrumeoMobile"
      'application_version' =>
      string(7) "0.0.245"
      'download_id' =>
      int(66065511222228)
      'version_external_identifier' =>
      int(834486669)
      'receipt_creation_date' =>
      string(27) "2020-02-04 14:41:24 Etc/GMT"
      'receipt_creation_date_ms' =>
      string(13) "1580827284000"
      'receipt_creation_date_pst' =>
      string(39) "2020-02-04 06:41:24 America/Los_Angeles"
      'request_date' =>
      string(27) "2020-02-04 14:41:26 Etc/GMT"
      'request_date_ms' =>
      string(13) "1580827286809"
      'request_date_pst' =>
      string(39) "2020-02-04 06:41:26 America/Los_Angeles"
      'original_purchase_date' =>
      string(27) "2020-02-04 14:38:14 Etc/GMT"
      'original_purchase_date_ms' =>
      string(13) "1580827094000"
      'original_purchase_date_pst' =>
      string(39) "2020-02-04 06:38:14 America/Los_Angeles"
      'original_application_version' =>
      string(7) "0.0.245"
      'in_app' =>
      array(1) {
        ...
      }
    }
    'latest_receipt_info' =>
    array(1) {
      [0] =>
      array(17) {
        ...
      }
    }
    'latest_receipt' =>
    string(6908) "MIIUOQYJKoZIhvcNAQcCoIIUKjCCFCYCAQExCzAJBgUrDgMCGgUAMIID2gYJKoZIhvcNAQcBoIIDywSCA8cxggPDMAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAKIwDQIBDQIBAQQFAgMB/DYwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGPBYTVAvUMBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEAEVjgNiw/syJYPfTmdqPiIwHAIBBQIBAQQUbC6Yk3D7xHaQDZ70DziXkyKS9dEwHgIBCAIBAQQWFhQyMDIwLTAyLTA0VDE0OjQxOjI0WjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
    'pending_renewal_info' =>
    array(1) {
      [0] =>
      array(4) {
        ...
      }
    }
  }
  protected $is_retryable =>
  bool(false)
}
---------------------------------------------------------
/app/ecommerce/src/Commands/FixSerializeErrorInAppPurchaseTables.php:126:
class stdClass#2811 (14) {
  public $id =>
  int(555)
  public $receipt =>
  string(6896) "MIIULgYJKoZIhvcNAQcCoIIUHzCCFBsCAQExCzAJBgUrDgMCGgUAMIIDzwYJKoZIhvcNAQcBoIIDwASCA7wxggO4MAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICALkwDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGPP5htTkiMBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEECmspbOF0FOu4NIFWrv/zDIwHAIBBQIBAQQU+PZSNO+UVKRkvtdJ+8jWBL8BbycwHgIBCAIBAQQWFhQyMDIwLTAyLTA0VDE3OjI1OjI3WjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
  public $transaction_id =>
  string(15) "270000644827584"
  public $request_type =>
  string(6) "mobile"
  public $notification_type =>
  NULL
  public $email =>
  string(21) "warehousman@gmail.com"
  public $brand =>
  string(6) "drumeo"
  public $valid =>
  int(1)
  public $validation_error =>
  NULL
  public $payment_id =>
  NULL
  public $subscription_id =>
  int(73972)
  public $raw_receipt_response =>
  string(23507) "O:42:"ReceiptValidator\\iTunes\\ProductionResponse":13:{s:14:"\000*\000result_code";i:0;s:12:"\000*\000bundle_id";s:23:"com.drumeo.DrumeoMobile";s:14:"\000*\000app_item_id";i:1460388277;s:25:"\000*\000original_purchase_date";O:13:"Carbon\\Carbon":3:{s:4:"date";s:26:"2020-02-04 17:23:30.000000";s:13:"timezone_type";i:1;s:8:"timezone";s:6:"+00:00";}s:15:"\000*\000request_date";O:13:"Carbon\\Carbon":3:{s:4:"date";s:26:"2020-02-04 17:25:29.000000";s:13:"timezone_type";i:1;s:8:"timezone";s:6:"+00:00";}s:24:"\000"...
  public $created_at =>
  string(19) "2020-02-04 17:25:28"
  public $updated_at =>
  string(19) "2020-02-04 17:25:29"
}
/app/ecommerce/src/Commands/FixSerializeErrorInAppPurchaseTables.php:127:
class ReceiptValidator\iTunes\ProductionResponse#2236 (13) {
  protected $result_code =>
  int(0)
  protected $bundle_id =>
  string(23) "com.drumeo.DrumeoMobile"
  protected $app_item_id =>
  int(1460388277)
  protected $original_purchase_date =>
  class Carbon\Carbon#2848 (3) {
    public $date =>
    string(26) "2020-02-04 17:23:30.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $request_date =>
  class Carbon\Carbon#2845 (3) {
    public $date =>
    string(26) "2020-02-04 17:25:29.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $receipt_creation_date =>
  class Carbon\Carbon#2844 (3) {
    public $date =>
    string(26) "2020-02-04 17:25:27.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $receipt =>
  array(18) {
    'receipt_type' =>
    string(10) "Production"
    'adam_id' =>
    int(1460388277)
    'app_item_id' =>
    int(1460388277)
    'bundle_id' =>
    string(23) "com.drumeo.DrumeoMobile"
    'application_version' =>
    string(7) "0.0.245"
    'download_id' =>
    int(67063258626338)
    'version_external_identifier' =>
    int(834486669)
    'receipt_creation_date' =>
    string(27) "2020-02-04 17:25:27 Etc/GMT"
    'receipt_creation_date_ms' =>
    string(13) "1580837127000"
    'receipt_creation_date_pst' =>
    string(39) "2020-02-04 09:25:27 America/Los_Angeles"
    'request_date' =>
    string(27) "2020-02-04 17:25:28 Etc/GMT"
    'request_date_ms' =>
    string(13) "1580837128584"
    'request_date_pst' =>
    string(39) "2020-02-04 09:25:28 America/Los_Angeles"
    'original_purchase_date' =>
    string(27) "2020-02-04 17:23:30 Etc/GMT"
    'original_purchase_date_ms' =>
    string(13) "1580837010000"
    'original_purchase_date_pst' =>
    string(39) "2020-02-04 09:23:30 America/Los_Angeles"
    'original_application_version' =>
    string(7) "0.0.245"
    'in_app' =>
    array(1) {
      [0] =>
      array(16) {
        ...
      }
    }
  }
  protected $latest_receipt =>
  string(6876) "MIIUHwYJKoZIhvcNAQcCoIIUEDCCFAwCAQExCzAJBgUrDgMCGgUAMIIDwAYJKoZIhvcNAQcBoIIDsQSCA60xggOpMAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICALkwDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGPP5htTkiMBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEECmspbOF0FOu4NIFWrv/zDIwHAIBBQIBAQQU+PZSNO+UVKRkvtdJ+8jWBL8BbycwHgIBCAIBAQQWFhQyMDIwLTAyLTA0VDE3OjI1OjI3WjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
  protected $latest_receipt_info =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PurchaseItem#2842 (13) {
      protected $quantity =>
      int(1)
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $web_order_line_item_id =>
      string(15) "270000226382463"
      protected $transaction_id =>
      string(15) "270000644827584"
      protected $original_transaction_id =>
      string(15) "270000644827584"
      protected $purchase_date =>
      class Carbon\Carbon#2832 (3) {
        ...
      }
      protected $original_purchase_date =>
      class Carbon\Carbon#2841 (3) {
        ...
      }
      protected $expires_date =>
      class Carbon\Carbon#2840 (3) {
        ...
      }
      protected $cancellation_date =>
      NULL
      protected $is_trial_period =>
      bool(true)
      protected $is_in_intro_offer_period =>
      bool(false)
      protected $promotional_offer_id =>
      NULL
      protected $raw_data =>
      array(17) {
        ...
      }
    }
  }
  protected $purchases =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PurchaseItem#2828 (13) {
      protected $quantity =>
      int(1)
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $web_order_line_item_id =>
      string(15) "270000226382463"
      protected $transaction_id =>
      string(15) "270000644827584"
      protected $original_transaction_id =>
      string(15) "270000644827584"
      protected $purchase_date =>
      class Carbon\Carbon#2831 (3) {
        ...
      }
      protected $original_purchase_date =>
      class Carbon\Carbon#2838 (3) {
        ...
      }
      protected $expires_date =>
      class Carbon\Carbon#2829 (3) {
        ...
      }
      protected $cancellation_date =>
      NULL
      protected $is_trial_period =>
      bool(true)
      protected $is_in_intro_offer_period =>
      bool(false)
      protected $promotional_offer_id =>
      NULL
      protected $raw_data =>
      array(16) {
        ...
      }
    }
  }
  protected $pending_renewal_info =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PendingRenewalInfo#2827 (7) {
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $auto_renew_product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $original_transaction_id =>
      string(15) "270000644827584"
      protected $auto_renew_status =>
      bool(true)
      protected $expiration_intent =>
      NULL
      protected $is_in_billing_retry_period =>
      NULL
      protected $raw_data =>
      array(4) {
        ...
      }
    }
  }
  protected $raw_data =>
  array(6) {
    'status' =>
    int(0)
    'environment' =>
    string(10) "Production"
    'receipt' =>
    array(18) {
      'receipt_type' =>
      string(10) "Production"
      'adam_id' =>
      int(1460388277)
      'app_item_id' =>
      int(1460388277)
      'bundle_id' =>
      string(23) "com.drumeo.DrumeoMobile"
      'application_version' =>
      string(7) "0.0.245"
      'download_id' =>
      int(67063258626338)
      'version_external_identifier' =>
      int(834486669)
      'receipt_creation_date' =>
      string(27) "2020-02-04 17:25:27 Etc/GMT"
      'receipt_creation_date_ms' =>
      string(13) "1580837127000"
      'receipt_creation_date_pst' =>
      string(39) "2020-02-04 09:25:27 America/Los_Angeles"
      'request_date' =>
      string(27) "2020-02-04 17:25:28 Etc/GMT"
      'request_date_ms' =>
      string(13) "1580837128584"
      'request_date_pst' =>
      string(39) "2020-02-04 09:25:28 America/Los_Angeles"
      'original_purchase_date' =>
      string(27) "2020-02-04 17:23:30 Etc/GMT"
      'original_purchase_date_ms' =>
      string(13) "1580837010000"
      'original_purchase_date_pst' =>
      string(39) "2020-02-04 09:23:30 America/Los_Angeles"
      'original_application_version' =>
      string(7) "0.0.245"
      'in_app' =>
      array(1) {
        ...
      }
    }
    'latest_receipt_info' =>
    array(1) {
      [0] =>
      array(17) {
        ...
      }
    }
    'latest_receipt' =>
    string(6876) "MIIUHwYJKoZIhvcNAQcCoIIUEDCCFAwCAQExCzAJBgUrDgMCGgUAMIIDwAYJKoZIhvcNAQcBoIIDsQSCA60xggOpMAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICALkwDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGPP5htTkiMBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEECmspbOF0FOu4NIFWrv/zDIwHAIBBQIBAQQU+PZSNO+UVKRkvtdJ+8jWBL8BbycwHgIBCAIBAQQWFhQyMDIwLTAyLTA0VDE3OjI1OjI3WjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
    'pending_renewal_info' =>
    array(1) {
      [0] =>
      array(4) {
        ...
      }
    }
  }
  protected $is_retryable =>
  bool(false)
}
---------------------------------------------------------
/app/ecommerce/src/Commands/FixSerializeErrorInAppPurchaseTables.php:126:
class stdClass#2812 (14) {
  public $id =>
  int(556)
  public $receipt =>
  string(5080) "ewoJInNpZ25hdHVyZSIgPSAiQXozLzRxalE5bit6UmcxTmowS2ZQenV3VGMrMWtWWjY4Mnd0TDdpM1ZTclBPTytmRHBXWGE4MkE1U3FxVEw0c2pER1RvSENaS3BQcFNBYjU1SG5vYnQycFY2ZERhNzd5TkF4Um9BRk5UMWRZK0FrcXpaK1F1bi9zMW1zekM1VEd0d2VvWTRrdFJYS1R4Rms0YXFBckFyV2hsSzBqZml4eUZ3WDk3Qk0vaDV3eHd3VGptcnFRY3p5TGt3TjNXS2hwOGJYelVoZCtSZ29OZkw1WXRKU1R2ZThuNmt1QVdkRndXM3EyZm1Yd3JuY3hOV1g2UURaeWNnaXZoS2pHNVU0dW9BcDV5UUNnVEw3ZmxiRUVxYlk0eXpWYzBURU1MZVJBVTVoMEl1Vm5Ya2VZSm1MeXBabHlsM3FTemgwdFptTHR4T01QSTdUL1Y5RUErZkpCQm5RN2h6RUFBQVdBTUlJRmZEQ0NCR1NnQXdJQkFn"...
  public $transaction_id =>
  NULL
  public $request_type =>
  string(12) "notification"
  public $notification_type =>
  string(7) "renewal"
  public $email =>
  NULL
  public $brand =>
  string(6) "drumeo"
  public $valid =>
  int(0)
  public $validation_error =>
  string(31) "All purchased items are expired"
  public $payment_id =>
  NULL
  public $subscription_id =>
  NULL
  public $raw_receipt_response =>
  string(11503) "O:42:"ReceiptValidator\\iTunes\\ProductionResponse":13:{s:14:"\000*\000result_code";i:0;s:12:"\000*\000bundle_id";s:23:"com.drumeo.DrumeoMobile";s:14:"\000*\000app_item_id";N;s:25:"\000*\000original_purchase_date";N;s:15:"\000*\000request_date";N;s:24:"\000*\000receipt_creation_date";N;s:10:"\000*\000receipt";a:24:{s:26:"original_purchase_date_pst";s:39:"2020-01-27 23:33:32 America/Los_Angeles";s:8:"quantity";s:1:"1";s:24:"unique_vendor_identifier";s:36:"FA2A85AE-3B0D-47B3-90E8-D64B958FCD77";s:4:"bvrs";s:7:"...
  public $created_at =>
  string(19) "2020-02-04 18:15:32"
  public $updated_at =>
  string(19) "2020-02-04 18:15:32"
}
/app/ecommerce/src/Commands/FixSerializeErrorInAppPurchaseTables.php:127:
class ReceiptValidator\iTunes\ProductionResponse#2236 (13) {
  protected $result_code =>
  int(0)
  protected $bundle_id =>
  string(23) "com.drumeo.DrumeoMobile"
  protected $app_item_id =>
  NULL
  protected $original_purchase_date =>
  NULL
  protected $request_date =>
  NULL
  protected $receipt_creation_date =>
  NULL
  protected $receipt =>
  array(24) {
    'original_purchase_date_pst' =>
    string(39) "2020-01-27 23:33:32 America/Los_Angeles"
    'quantity' =>
    string(1) "1"
    'unique_vendor_identifier' =>
    string(36) "FA2A85AE-3B0D-47B3-90E8-D64B958FCD77"
    'bvrs' =>
    string(7) "0.0.245"
    'expires_date_formatted' =>
    string(27) "2020-03-04 18:13:30 Etc/GMT"
    'is_in_intro_offer_period' =>
    string(5) "false"
    'purchase_date_ms' =>
    string(13) "1580840010000"
    'expires_date_formatted_pst' =>
    string(39) "2020-03-04 10:13:30 America/Los_Angeles"
    'is_trial_period' =>
    string(5) "false"
    'item_id' =>
    string(10) "1477997454"
    'unique_identifier' =>
    string(40) "d7de506782a968905b08697a956433041875db6b"
    'original_transaction_id' =>
    string(15) "300000540372704"
    'subscription_group_identifier' =>
    string(8) "20531400"
    'app_item_id' =>
    string(10) "1460388277"
    'transaction_id' =>
    string(15) "300000544583084"
    'web_order_line_item_id' =>
    string(15) "300000196027566"
    'version_external_identifier' =>
    string(9) "834486669"
    'purchase_date' =>
    string(27) "2020-02-04 18:13:30 Etc/GMT"
    'product_id' =>
    string(25) "drumeo_app_monthly_member"
    'expires_date' =>
    string(13) "1583345610000"
    'original_purchase_date' =>
    string(27) "2020-01-28 07:33:32 Etc/GMT"
    'purchase_date_pst' =>
    string(39) "2020-02-04 10:13:30 America/Los_Angeles"
    'bid' =>
    string(23) "com.drumeo.DrumeoMobile"
    'original_purchase_date_ms' =>
    string(13) "1580196812000"
  }
  protected $latest_receipt =>
  NULL
  protected $latest_receipt_info =>
  array(0) {
  }
  protected $purchases =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PurchaseItem#2827 (13) {
      protected $quantity =>
      int(1)
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $web_order_line_item_id =>
      string(15) "300000196027566"
      protected $transaction_id =>
      string(15) "300000544583084"
      protected $original_transaction_id =>
      string(15) "300000540372704"
      protected $purchase_date =>
      class Carbon\Carbon#2828 (3) {
        ...
      }
      protected $original_purchase_date =>
      class Carbon\Carbon#2829 (3) {
        ...
      }
      protected $expires_date =>
      class Carbon\Carbon#2838 (3) {
        ...
      }
      protected $cancellation_date =>
      NULL
      protected $is_trial_period =>
      bool(false)
      protected $is_in_intro_offer_period =>
      bool(false)
      protected $promotional_offer_id =>
      NULL
      protected $raw_data =>
      array(24) {
        ...
      }
    }
  }
  protected $pending_renewal_info =>
  array(0) {
  }
  protected $raw_data =>
  array(6) {
    'auto_renew_status' =>
    int(1)
    'status' =>
    int(0)
    'auto_renew_product_id' =>
    string(25) "drumeo_app_monthly_member"
    'receipt' =>
    array(24) {
      'original_purchase_date_pst' =>
      string(39) "2020-01-27 23:33:32 America/Los_Angeles"
      'quantity' =>
      string(1) "1"
      'unique_vendor_identifier' =>
      string(36) "FA2A85AE-3B0D-47B3-90E8-D64B958FCD77"
      'bvrs' =>
      string(7) "0.0.245"
      'expires_date_formatted' =>
      string(27) "2020-03-04 18:13:30 Etc/GMT"
      'is_in_intro_offer_period' =>
      string(5) "false"
      'purchase_date_ms' =>
      string(13) "1580840010000"
      'expires_date_formatted_pst' =>
      string(39) "2020-03-04 10:13:30 America/Los_Angeles"
      'is_trial_period' =>
      string(5) "false"
      'item_id' =>
      string(10) "1477997454"
      'unique_identifier' =>
      string(40) "d7de506782a968905b08697a956433041875db6b"
      'original_transaction_id' =>
      string(15) "300000540372704"
      'subscription_group_identifier' =>
      string(8) "20531400"
      'app_item_id' =>
      string(10) "1460388277"
      'transaction_id' =>
      string(15) "300000544583084"
      'web_order_line_item_id' =>
      string(15) "300000196027566"
      'version_external_identifier' =>
      string(9) "834486669"
      'purchase_date' =>
      string(27) "2020-02-04 18:13:30 Etc/GMT"
      'product_id' =>
      string(25) "drumeo_app_monthly_member"
      'expires_date' =>
      string(13) "1583345610000"
      'original_purchase_date' =>
      string(27) "2020-01-28 07:33:32 Etc/GMT"
      'purchase_date_pst' =>
      string(39) "2020-02-04 10:13:30 America/Los_Angeles"
      'bid' =>
      string(23) "com.drumeo.DrumeoMobile"
      'original_purchase_date_ms' =>
      string(13) "1580196812000"
    }
    'latest_receipt_info' =>
    array(23) {
      'original_purchase_date_pst' =>
      string(39) "2020-01-27 23:33:32 America/Los_Angeles"
      'quantity' =>
      string(1) "1"
      'unique_vendor_identifier' =>
      string(36) "FA2A85AE-3B0D-47B3-90E8-D64B958FCD77"
      'bvrs' =>
      string(7) "0.0.245"
      'expires_date_formatted' =>
      string(27) "2020-03-04 18:13:30 Etc/GMT"
      'is_in_intro_offer_period' =>
      string(5) "false"
      'purchase_date_ms' =>
      string(13) "1580840010000"
      'expires_date_formatted_pst' =>
      string(39) "2020-03-04 10:13:30 America/Los_Angeles"
      'is_trial_period' =>
      string(5) "false"
      'item_id' =>
      string(10) "1477997454"
      'unique_identifier' =>
      string(40) "d7de506782a968905b08697a956433041875db6b"
      'original_transaction_id' =>
      string(15) "300000540372704"
      'subscription_group_identifier' =>
      string(8) "20531400"
      'app_item_id' =>
      string(10) "1460388277"
      'transaction_id' =>
      string(15) "300000544583084"
      'web_order_line_item_id' =>
      string(15) "300000196027566"
      'purchase_date' =>
      string(27) "2020-02-04 18:13:30 Etc/GMT"
      'product_id' =>
      string(25) "drumeo_app_monthly_member"
      'expires_date' =>
      string(13) "1583345610000"
      'original_purchase_date' =>
      string(27) "2020-01-28 07:33:32 Etc/GMT"
      'purchase_date_pst' =>
      string(39) "2020-02-04 10:13:30 America/Los_Angeles"
      'bid' =>
      string(23) "com.drumeo.DrumeoMobile"
      'original_purchase_date_ms' =>
      string(13) "1580196812000"
    }
    'latest_receipt' =>
    string(5000) "ewoJInNpZ25hdHVyZSIgPSAiQTVLaFplSmtGZ2hXRkZzaW9RQklJTmdWWHI2RjRYMFRHdTlFSzM1dVNQN3dmbHh0RktQZjBMeVhHQnNDRDNudDk4M1ZRbEtvUndFTFg4cWVJa1YvbUY3a09hQlRlQWxDdnFlTTFYaktCbFEwdUNtTU9xNm1MZVg4SUZCcnZ6QXExSXNudDlHU091N3dob05DekFoMzdwUWJQN0FxMGZMbjFYTjVtNEFOVFo5WFJRYW5OeG51L01hTlZWdURZN2p3N1JqMVBjMkpMbHFaV1ZQVXZhZTN5cmk3dzZQNlBVd210aFFKOXFVTzRqeU0xdHNlc3VOM095ZTNxVWcrM0lmU0c4M3N6Y0w0K0htbzlHMEh2OUJwT2tBaHlJUFE3NVk2VWkrQzFiSVU5NG1SbU5wZkhLaEtPTE9jb0VTU0xDMVhBL2w3Z2MrbHRJYmxGTzJteE0xRjJLNEFBQVdBTUlJRmZEQ0NCR1NnQXdJQkFn"...
  }
  protected $is_retryable =>
  bool(false)
}
---------------------------------------------------------
/app/ecommerce/src/Commands/FixSerializeErrorInAppPurchaseTables.php:126:
class stdClass#2813 (14) {
  public $id =>
  int(557)
  public $receipt =>
  string(6916) "MIIUPQYJKoZIhvcNAQcCoIIULjCCFCoCAQExCzAJBgUrDgMCGgUAMIID3gYJKoZIhvcNAQcBoIIDzwSCA8sxggPHMAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAL0wDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGUs3J0y1/MBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEJ7poaMNsyVWLOsmqzeb4QowHAIBBQIBAQQUzj7DH+snblF9VsuIGv7wwQp6BKMwHgIBCAIBAQQWFhQyMDIwLTAyLTA0VDE5OjMzOjM4WjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
  public $transaction_id =>
  string(15) "510000446267434"
  public $request_type =>
  string(6) "mobile"
  public $notification_type =>
  NULL
  public $email =>
  string(19) "acarp0904@yahoo.com"
  public $brand =>
  string(6) "drumeo"
  public $valid =>
  int(1)
  public $validation_error =>
  NULL
  public $payment_id =>
  NULL
  public $subscription_id =>
  int(73975)
  public $raw_receipt_response =>
  string(23547) "O:42:"ReceiptValidator\\iTunes\\ProductionResponse":13:{s:14:"\000*\000result_code";i:0;s:12:"\000*\000bundle_id";s:23:"com.drumeo.DrumeoMobile";s:14:"\000*\000app_item_id";i:1460388277;s:25:"\000*\000original_purchase_date";O:13:"Carbon\\Carbon":3:{s:4:"date";s:26:"2020-02-04 02:20:07.000000";s:13:"timezone_type";i:1;s:8:"timezone";s:6:"+00:00";}s:15:"\000*\000request_date";O:13:"Carbon\\Carbon":3:{s:4:"date";s:26:"2020-02-04 19:33:40.000000";s:13:"timezone_type";i:1;s:8:"timezone";s:6:"+00:00";}s:24:"\000"...
  public $created_at =>
  string(19) "2020-02-04 19:33:39"
  public $updated_at =>
  string(19) "2020-02-04 19:33:41"
}
/app/ecommerce/src/Commands/FixSerializeErrorInAppPurchaseTables.php:127:
class ReceiptValidator\iTunes\ProductionResponse#2236 (13) {
  protected $result_code =>
  int(0)
  protected $bundle_id =>
  string(23) "com.drumeo.DrumeoMobile"
  protected $app_item_id =>
  int(1460388277)
  protected $original_purchase_date =>
  class Carbon\Carbon#2827 (3) {
    public $date =>
    string(26) "2020-02-04 02:20:07.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $request_date =>
  class Carbon\Carbon#2838 (3) {
    public $date =>
    string(26) "2020-02-04 19:33:40.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $receipt_creation_date =>
  class Carbon\Carbon#2829 (3) {
    public $date =>
    string(26) "2020-02-04 19:33:38.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $receipt =>
  array(18) {
    'receipt_type' =>
    string(10) "Production"
    'adam_id' =>
    int(1460388277)
    'app_item_id' =>
    int(1460388277)
    'bundle_id' =>
    string(23) "com.drumeo.DrumeoMobile"
    'application_version' =>
    string(7) "0.0.245"
    'download_id' =>
    int(91043807833471)
    'version_external_identifier' =>
    int(834486669)
    'receipt_creation_date' =>
    string(27) "2020-02-04 19:33:38 Etc/GMT"
    'receipt_creation_date_ms' =>
    string(13) "1580844818000"
    'receipt_creation_date_pst' =>
    string(39) "2020-02-04 11:33:38 America/Los_Angeles"
    'request_date' =>
    string(27) "2020-02-04 19:33:40 Etc/GMT"
    'request_date_ms' =>
    string(13) "1580844820009"
    'request_date_pst' =>
    string(39) "2020-02-04 11:33:40 America/Los_Angeles"
    'original_purchase_date' =>
    string(27) "2020-02-04 02:20:07 Etc/GMT"
    'original_purchase_date_ms' =>
    string(13) "1580782807000"
    'original_purchase_date_pst' =>
    string(39) "2020-02-03 18:20:07 America/Los_Angeles"
    'original_application_version' =>
    string(7) "0.0.245"
    'in_app' =>
    array(1) {
      [0] =>
      array(16) {
        ...
      }
    }
  }
  protected $latest_receipt =>
  string(6896) "MIIULgYJKoZIhvcNAQcCoIIUHzCCFBsCAQExCzAJBgUrDgMCGgUAMIIDzwYJKoZIhvcNAQcBoIIDwASCA7wxggO4MAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAL0wDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGUs3J0y1/MBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEJ7poaMNsyVWLOsmqzeb4QowHAIBBQIBAQQUzj7DH+snblF9VsuIGv7wwQp6BKMwHgIBCAIBAQQWFhQyMDIwLTAyLTA0VDE5OjMzOjM4WjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
  protected $latest_receipt_info =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PurchaseItem#2828 (13) {
      protected $quantity =>
      int(1)
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $web_order_line_item_id =>
      string(15) "510000163407040"
      protected $transaction_id =>
      string(15) "510000446267434"
      protected $original_transaction_id =>
      string(15) "510000446267434"
      protected $purchase_date =>
      class Carbon\Carbon#2831 (3) {
        ...
      }
      protected $original_purchase_date =>
      class Carbon\Carbon#2842 (3) {
        ...
      }
      protected $expires_date =>
      class Carbon\Carbon#2840 (3) {
        ...
      }
      protected $cancellation_date =>
      NULL
      protected $is_trial_period =>
      bool(true)
      protected $is_in_intro_offer_period =>
      bool(false)
      protected $promotional_offer_id =>
      NULL
      protected $raw_data =>
      array(17) {
        ...
      }
    }
  }
  protected $purchases =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PurchaseItem#2841 (13) {
      protected $quantity =>
      int(1)
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $web_order_line_item_id =>
      string(15) "510000163407040"
      protected $transaction_id =>
      string(15) "510000446267434"
      protected $original_transaction_id =>
      string(15) "510000446267434"
      protected $purchase_date =>
      class Carbon\Carbon#2832 (3) {
        ...
      }
      protected $original_purchase_date =>
      class Carbon\Carbon#2844 (3) {
        ...
      }
      protected $expires_date =>
      class Carbon\Carbon#2845 (3) {
        ...
      }
      protected $cancellation_date =>
      NULL
      protected $is_trial_period =>
      bool(true)
      protected $is_in_intro_offer_period =>
      bool(false)
      protected $promotional_offer_id =>
      NULL
      protected $raw_data =>
      array(16) {
        ...
      }
    }
  }
  protected $pending_renewal_info =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PendingRenewalInfo#2848 (7) {
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $auto_renew_product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $original_transaction_id =>
      string(15) "510000446267434"
      protected $auto_renew_status =>
      bool(true)
      protected $expiration_intent =>
      NULL
      protected $is_in_billing_retry_period =>
      NULL
      protected $raw_data =>
      array(4) {
        ...
      }
    }
  }
  protected $raw_data =>
  array(6) {
    'status' =>
    int(0)
    'environment' =>
    string(10) "Production"
    'receipt' =>
    array(18) {
      'receipt_type' =>
      string(10) "Production"
      'adam_id' =>
      int(1460388277)
      'app_item_id' =>
      int(1460388277)
      'bundle_id' =>
      string(23) "com.drumeo.DrumeoMobile"
      'application_version' =>
      string(7) "0.0.245"
      'download_id' =>
      int(91043807833471)
      'version_external_identifier' =>
      int(834486669)
      'receipt_creation_date' =>
      string(27) "2020-02-04 19:33:38 Etc/GMT"
      'receipt_creation_date_ms' =>
      string(13) "1580844818000"
      'receipt_creation_date_pst' =>
      string(39) "2020-02-04 11:33:38 America/Los_Angeles"
      'request_date' =>
      string(27) "2020-02-04 19:33:40 Etc/GMT"
      'request_date_ms' =>
      string(13) "1580844820009"
      'request_date_pst' =>
      string(39) "2020-02-04 11:33:40 America/Los_Angeles"
      'original_purchase_date' =>
      string(27) "2020-02-04 02:20:07 Etc/GMT"
      'original_purchase_date_ms' =>
      string(13) "1580782807000"
      'original_purchase_date_pst' =>
      string(39) "2020-02-03 18:20:07 America/Los_Angeles"
      'original_application_version' =>
      string(7) "0.0.245"
      'in_app' =>
      array(1) {
        ...
      }
    }
    'latest_receipt_info' =>
    array(1) {
      [0] =>
      array(17) {
        ...
      }
    }
    'latest_receipt' =>
    string(6896) "MIIULgYJKoZIhvcNAQcCoIIUHzCCFBsCAQExCzAJBgUrDgMCGgUAMIIDzwYJKoZIhvcNAQcBoIIDwASCA7wxggO4MAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAL0wDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGUs3J0y1/MBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEJ7poaMNsyVWLOsmqzeb4QowHAIBBQIBAQQUzj7DH+snblF9VsuIGv7wwQp6BKMwHgIBCAIBAQQWFhQyMDIwLTAyLTA0VDE5OjMzOjM4WjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
    'pending_renewal_info' =>
    array(1) {
      [0] =>
      array(4) {
        ...
      }
    }
  }
  protected $is_retryable =>
  bool(false)
}
---------------------------------------------------------
/app/ecommerce/src/Commands/FixSerializeErrorInAppPurchaseTables.php:126:
class stdClass#2814 (14) {
  public $id =>
  int(558)
  public $receipt =>
  string(6896) "MIIULwYJKoZIhvcNAQcCoIIUIDCCFBwCAQExCzAJBgUrDgMCGgUAMIID0AYJKoZIhvcNAQcBoIIDwQSCA70xggO5MAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAM8wDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGN4k1ShzxMBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4xNzYwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEIX0O2Bkg/UCC13ga5zV0gIwHAIBBQIBAQQUlUPj+JNExfP/Ar1BPEvoLF6s/1cwHgIBCAIBAQQWFhQyMDIwLTAyLTA0VDIyOjAwOjEyWjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
  public $transaction_id =>
  string(15) "210000667309685"
  public $request_type =>
  string(6) "mobile"
  public $notification_type =>
  NULL
  public $email =>
  string(21) "ethancoomer@gmail.com"
  public $brand =>
  string(6) "drumeo"
  public $valid =>
  int(1)
  public $validation_error =>
  NULL
  public $payment_id =>
  NULL
  public $subscription_id =>
  int(73982)
  public $raw_receipt_response =>
  string(23523) "O:42:"ReceiptValidator\\iTunes\\ProductionResponse":13:{s:14:"\000*\000result_code";i:0;s:12:"\000*\000bundle_id";s:23:"com.drumeo.DrumeoMobile";s:14:"\000*\000app_item_id";i:1460388277;s:25:"\000*\000original_purchase_date";O:13:"Carbon\\Carbon":3:{s:4:"date";s:26:"2020-01-13 15:29:56.000000";s:13:"timezone_type";i:1;s:8:"timezone";s:6:"+00:00";}s:15:"\000*\000request_date";O:13:"Carbon\\Carbon":3:{s:4:"date";s:26:"2020-02-04 22:00:14.000000";s:13:"timezone_type";i:1;s:8:"timezone";s:6:"+00:00";}s:24:"\000"...
  public $created_at =>
  string(19) "2020-02-04 22:00:12"
  public $updated_at =>
  string(19) "2020-02-04 22:00:13"
}
/app/ecommerce/src/Commands/FixSerializeErrorInAppPurchaseTables.php:127:
class ReceiptValidator\iTunes\ProductionResponse#2236 (13) {
  protected $result_code =>
  int(0)
  protected $bundle_id =>
  string(23) "com.drumeo.DrumeoMobile"
  protected $app_item_id =>
  int(1460388277)
  protected $original_purchase_date =>
  class Carbon\Carbon#2848 (3) {
    public $date =>
    string(26) "2020-01-13 15:29:56.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $request_date =>
  class Carbon\Carbon#2841 (3) {
    public $date =>
    string(26) "2020-02-04 22:00:14.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $receipt_creation_date =>
  class Carbon\Carbon#2845 (3) {
    public $date =>
    string(26) "2020-02-04 22:00:12.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $receipt =>
  array(18) {
    'receipt_type' =>
    string(10) "Production"
    'adam_id' =>
    int(1460388277)
    'app_item_id' =>
    int(1460388277)
    'bundle_id' =>
    string(23) "com.drumeo.DrumeoMobile"
    'application_version' =>
    string(7) "0.0.245"
    'download_id' =>
    int(61062444096753)
    'version_external_identifier' =>
    int(834486669)
    'receipt_creation_date' =>
    string(27) "2020-02-04 22:00:12 Etc/GMT"
    'receipt_creation_date_ms' =>
    string(13) "1580853612000"
    'receipt_creation_date_pst' =>
    string(39) "2020-02-04 14:00:12 America/Los_Angeles"
    'request_date' =>
    string(27) "2020-02-04 22:00:13 Etc/GMT"
    'request_date_ms' =>
    string(13) "1580853613524"
    'request_date_pst' =>
    string(39) "2020-02-04 14:00:13 America/Los_Angeles"
    'original_purchase_date' =>
    string(27) "2020-01-13 15:29:56 Etc/GMT"
    'original_purchase_date_ms' =>
    string(13) "1578929396000"
    'original_purchase_date_pst' =>
    string(39) "2020-01-13 07:29:56 America/Los_Angeles"
    'original_application_version' =>
    string(7) "0.0.176"
    'in_app' =>
    array(1) {
      [0] =>
      array(16) {
        ...
      }
    }
  }
  protected $latest_receipt =>
  string(6884) "MIIUJwYJKoZIhvcNAQcCoIIUGDCCFBQCAQExCzAJBgUrDgMCGgUAMIIDyAYJKoZIhvcNAQcBoIIDuQSCA7UxggOxMAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAM8wDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGN4k1ShzxMBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4xNzYwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEIX0O2Bkg/UCC13ga5zV0gIwHAIBBQIBAQQUlUPj+JNExfP/Ar1BPEvoLF6s/1cwHgIBCAIBAQQWFhQyMDIwLTAyLTA0VDIyOjAwOjEyWjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
  protected $latest_receipt_info =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PurchaseItem#2844 (13) {
      protected $quantity =>
      int(1)
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $web_order_line_item_id =>
      string(15) "210000228918776"
      protected $transaction_id =>
      string(15) "210000667309685"
      protected $original_transaction_id =>
      string(15) "210000667309685"
      protected $purchase_date =>
      class Carbon\Carbon#2832 (3) {
        ...
      }
      protected $original_purchase_date =>
      class Carbon\Carbon#2828 (3) {
        ...
      }
      protected $expires_date =>
      class Carbon\Carbon#2840 (3) {
        ...
      }
      protected $cancellation_date =>
      NULL
      protected $is_trial_period =>
      bool(true)
      protected $is_in_intro_offer_period =>
      bool(false)
      protected $promotional_offer_id =>
      NULL
      protected $raw_data =>
      array(17) {
        ...
      }
    }
  }
  protected $purchases =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PurchaseItem#2842 (13) {
      protected $quantity =>
      int(1)
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $web_order_line_item_id =>
      string(15) "210000228918776"
      protected $transaction_id =>
      string(15) "210000667309685"
      protected $original_transaction_id =>
      string(15) "210000667309685"
      protected $purchase_date =>
      class Carbon\Carbon#2831 (3) {
        ...
      }
      protected $original_purchase_date =>
      class Carbon\Carbon#2829 (3) {
        ...
      }
      protected $expires_date =>
      class Carbon\Carbon#2838 (3) {
        ...
      }
      protected $cancellation_date =>
      NULL
      protected $is_trial_period =>
      bool(true)
      protected $is_in_intro_offer_period =>
      bool(false)
      protected $promotional_offer_id =>
      NULL
      protected $raw_data =>
      array(16) {
        ...
      }
    }
  }
  protected $pending_renewal_info =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PendingRenewalInfo#2827 (7) {
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $auto_renew_product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $original_transaction_id =>
      string(15) "210000667309685"
      protected $auto_renew_status =>
      bool(true)
      protected $expiration_intent =>
      NULL
      protected $is_in_billing_retry_period =>
      NULL
      protected $raw_data =>
      array(4) {
        ...
      }
    }
  }
  protected $raw_data =>
  array(6) {
    'status' =>
    int(0)
    'environment' =>
    string(10) "Production"
    'receipt' =>
    array(18) {
      'receipt_type' =>
      string(10) "Production"
      'adam_id' =>
      int(1460388277)
      'app_item_id' =>
      int(1460388277)
      'bundle_id' =>
      string(23) "com.drumeo.DrumeoMobile"
      'application_version' =>
      string(7) "0.0.245"
      'download_id' =>
      int(61062444096753)
      'version_external_identifier' =>
      int(834486669)
      'receipt_creation_date' =>
      string(27) "2020-02-04 22:00:12 Etc/GMT"
      'receipt_creation_date_ms' =>
      string(13) "1580853612000"
      'receipt_creation_date_pst' =>
      string(39) "2020-02-04 14:00:12 America/Los_Angeles"
      'request_date' =>
      string(27) "2020-02-04 22:00:13 Etc/GMT"
      'request_date_ms' =>
      string(13) "1580853613524"
      'request_date_pst' =>
      string(39) "2020-02-04 14:00:13 America/Los_Angeles"
      'original_purchase_date' =>
      string(27) "2020-01-13 15:29:56 Etc/GMT"
      'original_purchase_date_ms' =>
      string(13) "1578929396000"
      'original_purchase_date_pst' =>
      string(39) "2020-01-13 07:29:56 America/Los_Angeles"
      'original_application_version' =>
      string(7) "0.0.176"
      'in_app' =>
      array(1) {
        ...
      }
    }
    'latest_receipt_info' =>
    array(1) {
      [0] =>
      array(17) {
        ...
      }
    }
    'latest_receipt' =>
    string(6884) "MIIUJwYJKoZIhvcNAQcCoIIUGDCCFBQCAQExCzAJBgUrDgMCGgUAMIIDyAYJKoZIhvcNAQcBoIIDuQSCA7UxggOxMAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAM8wDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGN4k1ShzxMBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4xNzYwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEIX0O2Bkg/UCC13ga5zV0gIwHAIBBQIBAQQUlUPj+JNExfP/Ar1BPEvoLF6s/1cwHgIBCAIBAQQWFhQyMDIwLTAyLTA0VDIyOjAwOjEyWjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
    'pending_renewal_info' =>
    array(1) {
      [0] =>
      array(4) {
        ...
      }
    }
  }
  protected $is_retryable =>
  bool(false)
}
---------------------------------------------------------
/app/ecommerce/src/Commands/FixSerializeErrorInAppPurchaseTables.php:126:
class stdClass#2815 (14) {
  public $id =>
  int(559)
  public $receipt =>
  string(6868) "MIIUGgYJKoZIhvcNAQcCoIIUCzCCFAcCAQExCzAJBgUrDgMCGgUAMIIDuwYJKoZIhvcNAQcBoIIDrASCA6gxggOkMAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAJ0wDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGTkTmLqi7MBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEJW2/Q9+TVuXOnf9ekRuEXMwHAIBBQIBAQQULr4SHIb2TpuVeL3mknrMAlGYdzkwHgIBCAIBAQQWFhQyMDIwLTAyLTA0VDIzOjIzOjIwWjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
  public $transaction_id =>
  string(15) "460000584850738"
  public $request_type =>
  string(6) "mobile"
  public $notification_type =>
  NULL
  public $email =>
  string(18) "kfeil@email.sc.edu"
  public $brand =>
  string(6) "drumeo"
  public $valid =>
  int(1)
  public $validation_error =>
  NULL
  public $payment_id =>
  NULL
  public $subscription_id =>
  int(73988)
  public $raw_receipt_response =>
  string(23603) "O:42:"ReceiptValidator\\iTunes\\ProductionResponse":13:{s:14:"\000*\000result_code";i:0;s:12:"\000*\000bundle_id";s:23:"com.drumeo.DrumeoMobile";s:14:"\000*\000app_item_id";i:1460388277;s:25:"\000*\000original_purchase_date";O:13:"Carbon\\Carbon":3:{s:4:"date";s:26:"2020-02-04 23:22:03.000000";s:13:"timezone_type";i:1;s:8:"timezone";s:6:"+00:00";}s:15:"\000*\000request_date";O:13:"Carbon\\Carbon":3:{s:4:"date";s:26:"2020-02-04 23:23:22.000000";s:13:"timezone_type";i:1;s:8:"timezone";s:6:"+00:00";}s:24:"\000"...
  public $created_at =>
  string(19) "2020-02-04 23:23:21"
  public $updated_at =>
  string(19) "2020-02-04 23:23:23"
}
/app/ecommerce/src/Commands/FixSerializeErrorInAppPurchaseTables.php:127:
class ReceiptValidator\iTunes\ProductionResponse#2236 (13) {
  protected $result_code =>
  int(0)
  protected $bundle_id =>
  string(23) "com.drumeo.DrumeoMobile"
  protected $app_item_id =>
  int(1460388277)
  protected $original_purchase_date =>
  class Carbon\Carbon#2827 (3) {
    public $date =>
    string(26) "2020-02-04 23:22:03.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $request_date =>
  class Carbon\Carbon#2842 (3) {
    public $date =>
    string(26) "2020-02-04 23:23:22.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $receipt_creation_date =>
  class Carbon\Carbon#2838 (3) {
    public $date =>
    string(26) "2020-02-04 23:23:21.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $receipt =>
  array(18) {
    'receipt_type' =>
    string(10) "Production"
    'adam_id' =>
    int(1460388277)
    'app_item_id' =>
    int(1460388277)
    'bundle_id' =>
    string(23) "com.drumeo.DrumeoMobile"
    'application_version' =>
    string(7) "0.0.245"
    'download_id' =>
    int(86057826560187)
    'version_external_identifier' =>
    int(834486669)
    'receipt_creation_date' =>
    string(27) "2020-02-04 23:23:21 Etc/GMT"
    'receipt_creation_date_ms' =>
    string(13) "1580858601000"
    'receipt_creation_date_pst' =>
    string(39) "2020-02-04 15:23:21 America/Los_Angeles"
    'request_date' =>
    string(27) "2020-02-04 23:23:22 Etc/GMT"
    'request_date_ms' =>
    string(13) "1580858602306"
    'request_date_pst' =>
    string(39) "2020-02-04 15:23:22 America/Los_Angeles"
    'original_purchase_date' =>
    string(27) "2020-02-04 23:22:03 Etc/GMT"
    'original_purchase_date_ms' =>
    string(13) "1580858523000"
    'original_purchase_date_pst' =>
    string(39) "2020-02-04 15:22:03 America/Los_Angeles"
    'original_application_version' =>
    string(7) "0.0.245"
    'in_app' =>
    array(1) {
      [0] =>
      array(16) {
        ...
      }
    }
  }
  protected $latest_receipt =>
  string(6924) "MIIURQYJKoZIhvcNAQcCoIIUNjCCFDICAQExCzAJBgUrDgMCGgUAMIID5gYJKoZIhvcNAQcBoIID1wSCA9MxggPPMAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAJ0wDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGTkTmLqi7MBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEJW2/Q9+TVuXOnf9ekRuEXMwHAIBBQIBAQQULr4SHIb2TpuVeL3mknrMAlGYdzkwHgIBCAIBAQQWFhQyMDIwLTAyLTA0VDIzOjIzOjIwWjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
  protected $latest_receipt_info =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PurchaseItem#2829 (13) {
      protected $quantity =>
      int(1)
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $web_order_line_item_id =>
      string(15) "460000216948314"
      protected $transaction_id =>
      string(15) "460000584850738"
      protected $original_transaction_id =>
      string(15) "460000584850738"
      protected $purchase_date =>
      class Carbon\Carbon#2831 (3) {
        ...
      }
      protected $original_purchase_date =>
      class Carbon\Carbon#2844 (3) {
        ...
      }
      protected $expires_date =>
      class Carbon\Carbon#2840 (3) {
        ...
      }
      protected $cancellation_date =>
      NULL
      protected $is_trial_period =>
      bool(true)
      protected $is_in_intro_offer_period =>
      bool(false)
      protected $promotional_offer_id =>
      NULL
      protected $raw_data =>
      array(17) {
        ...
      }
    }
  }
  protected $purchases =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PurchaseItem#2828 (13) {
      protected $quantity =>
      int(1)
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $web_order_line_item_id =>
      string(15) "460000216948314"
      protected $transaction_id =>
      string(15) "460000584850738"
      protected $original_transaction_id =>
      string(15) "460000584850738"
      protected $purchase_date =>
      class Carbon\Carbon#2832 (3) {
        ...
      }
      protected $original_purchase_date =>
      class Carbon\Carbon#2845 (3) {
        ...
      }
      protected $expires_date =>
      class Carbon\Carbon#2841 (3) {
        ...
      }
      protected $cancellation_date =>
      NULL
      protected $is_trial_period =>
      bool(true)
      protected $is_in_intro_offer_period =>
      bool(false)
      protected $promotional_offer_id =>
      NULL
      protected $raw_data =>
      array(16) {
        ...
      }
    }
  }
  protected $pending_renewal_info =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PendingRenewalInfo#2848 (7) {
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $auto_renew_product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $original_transaction_id =>
      string(15) "460000584850738"
      protected $auto_renew_status =>
      bool(true)
      protected $expiration_intent =>
      NULL
      protected $is_in_billing_retry_period =>
      NULL
      protected $raw_data =>
      array(4) {
        ...
      }
    }
  }
  protected $raw_data =>
  array(6) {
    'status' =>
    int(0)
    'environment' =>
    string(10) "Production"
    'receipt' =>
    array(18) {
      'receipt_type' =>
      string(10) "Production"
      'adam_id' =>
      int(1460388277)
      'app_item_id' =>
      int(1460388277)
      'bundle_id' =>
      string(23) "com.drumeo.DrumeoMobile"
      'application_version' =>
      string(7) "0.0.245"
      'download_id' =>
      int(86057826560187)
      'version_external_identifier' =>
      int(834486669)
      'receipt_creation_date' =>
      string(27) "2020-02-04 23:23:21 Etc/GMT"
      'receipt_creation_date_ms' =>
      string(13) "1580858601000"
      'receipt_creation_date_pst' =>
      string(39) "2020-02-04 15:23:21 America/Los_Angeles"
      'request_date' =>
      string(27) "2020-02-04 23:23:22 Etc/GMT"
      'request_date_ms' =>
      string(13) "1580858602306"
      'request_date_pst' =>
      string(39) "2020-02-04 15:23:22 America/Los_Angeles"
      'original_purchase_date' =>
      string(27) "2020-02-04 23:22:03 Etc/GMT"
      'original_purchase_date_ms' =>
      string(13) "1580858523000"
      'original_purchase_date_pst' =>
      string(39) "2020-02-04 15:22:03 America/Los_Angeles"
      'original_application_version' =>
      string(7) "0.0.245"
      'in_app' =>
      array(1) {
        ...
      }
    }
    'latest_receipt_info' =>
    array(1) {
      [0] =>
      array(17) {
        ...
      }
    }
    'latest_receipt' =>
    string(6924) "MIIURQYJKoZIhvcNAQcCoIIUNjCCFDICAQExCzAJBgUrDgMCGgUAMIID5gYJKoZIhvcNAQcBoIID1wSCA9MxggPPMAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAJ0wDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGTkTmLqi7MBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEJW2/Q9+TVuXOnf9ekRuEXMwHAIBBQIBAQQULr4SHIb2TpuVeL3mknrMAlGYdzkwHgIBCAIBAQQWFhQyMDIwLTAyLTA0VDIzOjIzOjIwWjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
    'pending_renewal_info' =>
    array(1) {
      [0] =>
      array(4) {
        ...
      }
    }
  }
  protected $is_retryable =>
  bool(false)
}
---------------------------------------------------------
/app/ecommerce/src/Commands/FixSerializeErrorInAppPurchaseTables.php:126:
class stdClass#2816 (14) {
  public $id =>
  int(560)
  public $receipt =>
  string(6880) "MIIUIwYJKoZIhvcNAQcCoIIUFDCCFBACAQExCzAJBgUrDgMCGgUAMIIDxAYJKoZIhvcNAQcBoIIDtQSCA7ExggOtMAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICALkwDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGG1dkUSiJMBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEELrIapQ0P+6OOyKeh6f+uMkwHAIBBQIBAQQUh80Q7FNSTI8IEkaHCbPm/r0I5iswHgIBCAIBAQQWFhQyMDIwLTAyLTA1VDAwOjEzOjA3WjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
  public $transaction_id =>
  string(15) "100000641532560"
  public $request_type =>
  string(6) "mobile"
  public $notification_type =>
  NULL
  public $email =>
  string(23) "juliaclissold@gmail.com"
  public $brand =>
  string(6) "drumeo"
  public $valid =>
  int(1)
  public $validation_error =>
  NULL
  public $payment_id =>
  NULL
  public $subscription_id =>
  int(73991)
  public $raw_receipt_response =>
  string(23499) "O:42:"ReceiptValidator\\iTunes\\ProductionResponse":13:{s:14:"\000*\000result_code";i:0;s:12:"\000*\000bundle_id";s:23:"com.drumeo.DrumeoMobile";s:14:"\000*\000app_item_id";i:1460388277;s:25:"\000*\000original_purchase_date";O:13:"Carbon\\Carbon":3:{s:4:"date";s:26:"2020-02-05 00:12:05.000000";s:13:"timezone_type";i:1;s:8:"timezone";s:6:"+00:00";}s:15:"\000*\000request_date";O:13:"Carbon\\Carbon":3:{s:4:"date";s:26:"2020-02-05 00:13:08.000000";s:13:"timezone_type";i:1;s:8:"timezone";s:6:"+00:00";}s:24:"\000"...
  public $created_at =>
  string(19) "2020-02-05 00:13:07"
  public $updated_at =>
  string(19) "2020-02-05 00:13:08"
}
/app/ecommerce/src/Commands/FixSerializeErrorInAppPurchaseTables.php:127:
class ReceiptValidator\iTunes\ProductionResponse#2236 (13) {
  protected $result_code =>
  int(0)
  protected $bundle_id =>
  string(23) "com.drumeo.DrumeoMobile"
  protected $app_item_id =>
  int(1460388277)
  protected $original_purchase_date =>
  class Carbon\Carbon#2848 (3) {
    public $date =>
    string(26) "2020-02-05 00:12:05.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $request_date =>
  class Carbon\Carbon#2828 (3) {
    public $date =>
    string(26) "2020-02-05 00:13:08.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $receipt_creation_date =>
  class Carbon\Carbon#2841 (3) {
    public $date =>
    string(26) "2020-02-05 00:13:07.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $receipt =>
  array(18) {
    'receipt_type' =>
    string(10) "Production"
    'adam_id' =>
    int(1460388277)
    'app_item_id' =>
    int(1460388277)
    'bundle_id' =>
    string(23) "com.drumeo.DrumeoMobile"
    'application_version' =>
    string(7) "0.0.245"
    'download_id' =>
    int(30062159145097)
    'version_external_identifier' =>
    int(834486669)
    'receipt_creation_date' =>
    string(27) "2020-02-05 00:13:07 Etc/GMT"
    'receipt_creation_date_ms' =>
    string(13) "1580861587000"
    'receipt_creation_date_pst' =>
    string(39) "2020-02-04 16:13:07 America/Los_Angeles"
    'request_date' =>
    string(27) "2020-02-05 00:13:08 Etc/GMT"
    'request_date_ms' =>
    string(13) "1580861588089"
    'request_date_pst' =>
    string(39) "2020-02-04 16:13:08 America/Los_Angeles"
    'original_purchase_date' =>
    string(27) "2020-02-05 00:12:05 Etc/GMT"
    'original_purchase_date_ms' =>
    string(13) "1580861525000"
    'original_purchase_date_pst' =>
    string(39) "2020-02-04 16:12:05 America/Los_Angeles"
    'original_application_version' =>
    string(7) "0.0.245"
    'in_app' =>
    array(1) {
      [0] =>
      array(16) {
        ...
      }
    }
  }
  protected $latest_receipt =>
  string(6872) "MIIUHAYJKoZIhvcNAQcCoIIUDTCCFAkCAQExCzAJBgUrDgMCGgUAMIIDvQYJKoZIhvcNAQcBoIIDrgSCA6oxggOmMAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICALkwDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGG1dkUSiJMBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEELrIapQ0P+6OOyKeh6f+uMkwHAIBBQIBAQQUh80Q7FNSTI8IEkaHCbPm/r0I5iswHgIBCAIBAQQWFhQyMDIwLTAyLTA1VDAwOjEzOjA3WjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
  protected $latest_receipt_info =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PurchaseItem#2845 (13) {
      protected $quantity =>
      int(1)
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $web_order_line_item_id =>
      string(15) "100000219745536"
      protected $transaction_id =>
      string(15) "100000641532560"
      protected $original_transaction_id =>
      string(15) "100000641532560"
      protected $purchase_date =>
      class Carbon\Carbon#2832 (3) {
        ...
      }
      protected $original_purchase_date =>
      class Carbon\Carbon#2829 (3) {
        ...
      }
      protected $expires_date =>
      class Carbon\Carbon#2840 (3) {
        ...
      }
      protected $cancellation_date =>
      NULL
      protected $is_trial_period =>
      bool(true)
      protected $is_in_intro_offer_period =>
      bool(false)
      protected $promotional_offer_id =>
      NULL
      protected $raw_data =>
      array(17) {
        ...
      }
    }
  }
  protected $purchases =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PurchaseItem#2844 (13) {
      protected $quantity =>
      int(1)
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $web_order_line_item_id =>
      string(15) "100000219745536"
      protected $transaction_id =>
      string(15) "100000641532560"
      protected $original_transaction_id =>
      string(15) "100000641532560"
      protected $purchase_date =>
      class Carbon\Carbon#2831 (3) {
        ...
      }
      protected $original_purchase_date =>
      class Carbon\Carbon#2838 (3) {
        ...
      }
      protected $expires_date =>
      class Carbon\Carbon#2842 (3) {
        ...
      }
      protected $cancellation_date =>
      NULL
      protected $is_trial_period =>
      bool(true)
      protected $is_in_intro_offer_period =>
      bool(false)
      protected $promotional_offer_id =>
      NULL
      protected $raw_data =>
      array(16) {
        ...
      }
    }
  }
  protected $pending_renewal_info =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PendingRenewalInfo#2827 (7) {
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $auto_renew_product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $original_transaction_id =>
      string(15) "100000641532560"
      protected $auto_renew_status =>
      bool(true)
      protected $expiration_intent =>
      NULL
      protected $is_in_billing_retry_period =>
      NULL
      protected $raw_data =>
      array(4) {
        ...
      }
    }
  }
  protected $raw_data =>
  array(6) {
    'status' =>
    int(0)
    'environment' =>
    string(10) "Production"
    'receipt' =>
    array(18) {
      'receipt_type' =>
      string(10) "Production"
      'adam_id' =>
      int(1460388277)
      'app_item_id' =>
      int(1460388277)
      'bundle_id' =>
      string(23) "com.drumeo.DrumeoMobile"
      'application_version' =>
      string(7) "0.0.245"
      'download_id' =>
      int(30062159145097)
      'version_external_identifier' =>
      int(834486669)
      'receipt_creation_date' =>
      string(27) "2020-02-05 00:13:07 Etc/GMT"
      'receipt_creation_date_ms' =>
      string(13) "1580861587000"
      'receipt_creation_date_pst' =>
      string(39) "2020-02-04 16:13:07 America/Los_Angeles"
      'request_date' =>
      string(27) "2020-02-05 00:13:08 Etc/GMT"
      'request_date_ms' =>
      string(13) "1580861588089"
      'request_date_pst' =>
      string(39) "2020-02-04 16:13:08 America/Los_Angeles"
      'original_purchase_date' =>
      string(27) "2020-02-05 00:12:05 Etc/GMT"
      'original_purchase_date_ms' =>
      string(13) "1580861525000"
      'original_purchase_date_pst' =>
      string(39) "2020-02-04 16:12:05 America/Los_Angeles"
      'original_application_version' =>
      string(7) "0.0.245"
      'in_app' =>
      array(1) {
        ...
      }
    }
    'latest_receipt_info' =>
    array(1) {
      [0] =>
      array(17) {
        ...
      }
    }
    'latest_receipt' =>
    string(6872) "MIIUHAYJKoZIhvcNAQcCoIIUDTCCFAkCAQExCzAJBgUrDgMCGgUAMIIDvQYJKoZIhvcNAQcBoIIDrgSCA6oxggOmMAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICALkwDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGG1dkUSiJMBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEELrIapQ0P+6OOyKeh6f+uMkwHAIBBQIBAQQUh80Q7FNSTI8IEkaHCbPm/r0I5iswHgIBCAIBAQQWFhQyMDIwLTAyLTA1VDAwOjEzOjA3WjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
    'pending_renewal_info' =>
    array(1) {
      [0] =>
      array(4) {
        ...
      }
    }
  }
  protected $is_retryable =>
  bool(false)
}
---------------------------------------------------------
/app/ecommerce/src/Commands/FixSerializeErrorInAppPurchaseTables.php:126:
class stdClass#2817 (14) {
  public $id =>
  int(561)
  public $receipt =>
  string(6880) "MIIUIgYJKoZIhvcNAQcCoIIUEzCCFA8CAQExCzAJBgUrDgMCGgUAMIIDwwYJKoZIhvcNAQcBoIIDtASCA7AxggOsMAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAIkwDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGTy127JP6MBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEI/W8pSFxSrCpSJAnrUY+BQwHAIBBQIBAQQUo/9ooEoxwaLT5a5h82xeUcanr8MwHgIBCAIBAQQWFhQyMDIwLTAyLTA1VDA0OjE4OjMxWjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
  public $transaction_id =>
  string(15) "470000568636790"
  public $request_type =>
  string(6) "mobile"
  public $notification_type =>
  NULL
  public $email =>
  string(23) "suziehughes@hotmail.com"
  public $brand =>
  string(6) "drumeo"
  public $valid =>
  int(1)
  public $validation_error =>
  NULL
  public $payment_id =>
  NULL
  public $subscription_id =>
  int(73998)
  public $raw_receipt_response =>
  string(23547) "O:42:"ReceiptValidator\\iTunes\\ProductionResponse":13:{s:14:"\000*\000result_code";i:0;s:12:"\000*\000bundle_id";s:23:"com.drumeo.DrumeoMobile";s:14:"\000*\000app_item_id";i:1460388277;s:25:"\000*\000original_purchase_date";O:13:"Carbon\\Carbon":3:{s:4:"date";s:26:"2020-02-05 04:14:55.000000";s:13:"timezone_type";i:1;s:8:"timezone";s:6:"+00:00";}s:15:"\000*\000request_date";O:13:"Carbon\\Carbon":3:{s:4:"date";s:26:"2020-02-05 04:18:33.000000";s:13:"timezone_type";i:1;s:8:"timezone";s:6:"+00:00";}s:24:"\000"...
  public $created_at =>
  string(19) "2020-02-05 04:18:32"
  public $updated_at =>
  string(19) "2020-02-05 04:18:33"
}
/app/ecommerce/src/Commands/FixSerializeErrorInAppPurchaseTables.php:127:
class ReceiptValidator\iTunes\ProductionResponse#2236 (13) {
  protected $result_code =>
  int(0)
  protected $bundle_id =>
  string(23) "com.drumeo.DrumeoMobile"
  protected $app_item_id =>
  int(1460388277)
  protected $original_purchase_date =>
  class Carbon\Carbon#2827 (3) {
    public $date =>
    string(26) "2020-02-05 04:14:55.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $request_date =>
  class Carbon\Carbon#2844 (3) {
    public $date =>
    string(26) "2020-02-05 04:18:33.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $receipt_creation_date =>
  class Carbon\Carbon#2842 (3) {
    public $date =>
    string(26) "2020-02-05 04:18:31.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $receipt =>
  array(18) {
    'receipt_type' =>
    string(10) "Production"
    'adam_id' =>
    int(1460388277)
    'app_item_id' =>
    int(1460388277)
    'bundle_id' =>
    string(23) "com.drumeo.DrumeoMobile"
    'application_version' =>
    string(7) "0.0.245"
    'download_id' =>
    int(87056687338490)
    'version_external_identifier' =>
    int(834486669)
    'receipt_creation_date' =>
    string(27) "2020-02-05 04:18:31 Etc/GMT"
    'receipt_creation_date_ms' =>
    string(13) "1580876311000"
    'receipt_creation_date_pst' =>
    string(39) "2020-02-04 20:18:31 America/Los_Angeles"
    'request_date' =>
    string(27) "2020-02-05 04:18:32 Etc/GMT"
    'request_date_ms' =>
    string(13) "1580876312546"
    'request_date_pst' =>
    string(39) "2020-02-04 20:18:32 America/Los_Angeles"
    'original_purchase_date' =>
    string(27) "2020-02-05 04:14:55 Etc/GMT"
    'original_purchase_date_ms' =>
    string(13) "1580876095000"
    'original_purchase_date_pst' =>
    string(39) "2020-02-04 20:14:55 America/Los_Angeles"
    'original_application_version' =>
    string(7) "0.0.245"
    'in_app' =>
    array(1) {
      [0] =>
      array(16) {
        ...
      }
    }
  }
  protected $latest_receipt =>
  string(6896) "MIIUMAYJKoZIhvcNAQcCoIIUITCCFB0CAQExCzAJBgUrDgMCGgUAMIID0QYJKoZIhvcNAQcBoIIDwgSCA74xggO6MAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAIkwDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGTy127JP6MBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEI/W8pSFxSrCpSJAnrUY+BQwHAIBBQIBAQQUo/9ooEoxwaLT5a5h82xeUcanr8MwHgIBCAIBAQQWFhQyMDIwLTAyLTA1VDA0OjE4OjMxWjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
  protected $latest_receipt_info =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PurchaseItem#2838 (13) {
      protected $quantity =>
      int(1)
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $web_order_line_item_id =>
      string(15) "470000206493208"
      protected $transaction_id =>
      string(15) "470000568636790"
      protected $original_transaction_id =>
      string(15) "470000568636790"
      protected $purchase_date =>
      class Carbon\Carbon#2831 (3) {
        ...
      }
      protected $original_purchase_date =>
      class Carbon\Carbon#2845 (3) {
        ...
      }
      protected $expires_date =>
      class Carbon\Carbon#2840 (3) {
        ...
      }
      protected $cancellation_date =>
      NULL
      protected $is_trial_period =>
      bool(true)
      protected $is_in_intro_offer_period =>
      bool(false)
      protected $promotional_offer_id =>
      NULL
      protected $raw_data =>
      array(17) {
        ...
      }
    }
  }
  protected $purchases =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PurchaseItem#2829 (13) {
      protected $quantity =>
      int(1)
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $web_order_line_item_id =>
      string(15) "470000206493208"
      protected $transaction_id =>
      string(15) "470000568636790"
      protected $original_transaction_id =>
      string(15) "470000568636790"
      protected $purchase_date =>
      class Carbon\Carbon#2832 (3) {
        ...
      }
      protected $original_purchase_date =>
      class Carbon\Carbon#2841 (3) {
        ...
      }
      protected $expires_date =>
      class Carbon\Carbon#2828 (3) {
        ...
      }
      protected $cancellation_date =>
      NULL
      protected $is_trial_period =>
      bool(true)
      protected $is_in_intro_offer_period =>
      bool(false)
      protected $promotional_offer_id =>
      NULL
      protected $raw_data =>
      array(16) {
        ...
      }
    }
  }
  protected $pending_renewal_info =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PendingRenewalInfo#2848 (7) {
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $auto_renew_product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $original_transaction_id =>
      string(15) "470000568636790"
      protected $auto_renew_status =>
      bool(true)
      protected $expiration_intent =>
      NULL
      protected $is_in_billing_retry_period =>
      NULL
      protected $raw_data =>
      array(4) {
        ...
      }
    }
  }
  protected $raw_data =>
  array(6) {
    'status' =>
    int(0)
    'environment' =>
    string(10) "Production"
    'receipt' =>
    array(18) {
      'receipt_type' =>
      string(10) "Production"
      'adam_id' =>
      int(1460388277)
      'app_item_id' =>
      int(1460388277)
      'bundle_id' =>
      string(23) "com.drumeo.DrumeoMobile"
      'application_version' =>
      string(7) "0.0.245"
      'download_id' =>
      int(87056687338490)
      'version_external_identifier' =>
      int(834486669)
      'receipt_creation_date' =>
      string(27) "2020-02-05 04:18:31 Etc/GMT"
      'receipt_creation_date_ms' =>
      string(13) "1580876311000"
      'receipt_creation_date_pst' =>
      string(39) "2020-02-04 20:18:31 America/Los_Angeles"
      'request_date' =>
      string(27) "2020-02-05 04:18:32 Etc/GMT"
      'request_date_ms' =>
      string(13) "1580876312546"
      'request_date_pst' =>
      string(39) "2020-02-04 20:18:32 America/Los_Angeles"
      'original_purchase_date' =>
      string(27) "2020-02-05 04:14:55 Etc/GMT"
      'original_purchase_date_ms' =>
      string(13) "1580876095000"
      'original_purchase_date_pst' =>
      string(39) "2020-02-04 20:14:55 America/Los_Angeles"
      'original_application_version' =>
      string(7) "0.0.245"
      'in_app' =>
      array(1) {
        ...
      }
    }
    'latest_receipt_info' =>
    array(1) {
      [0] =>
      array(17) {
        ...
      }
    }
    'latest_receipt' =>
    string(6896) "MIIUMAYJKoZIhvcNAQcCoIIUITCCFB0CAQExCzAJBgUrDgMCGgUAMIID0QYJKoZIhvcNAQcBoIIDwgSCA74xggO6MAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAIkwDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGTy127JP6MBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEI/W8pSFxSrCpSJAnrUY+BQwHAIBBQIBAQQUo/9ooEoxwaLT5a5h82xeUcanr8MwHgIBCAIBAQQWFhQyMDIwLTAyLTA1VDA0OjE4OjMxWjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
    'pending_renewal_info' =>
    array(1) {
      [0] =>
      array(4) {
        ...
      }
    }
  }
  protected $is_retryable =>
  bool(false)
}
---------------------------------------------------------
/app/ecommerce/src/Commands/FixSerializeErrorInAppPurchaseTables.php:126:
class stdClass#2818 (14) {
  public $id =>
  int(562)
  public $receipt =>
  string(6896) "MIIUMAYJKoZIhvcNAQcCoIIUITCCFB0CAQExCzAJBgUrDgMCGgUAMIID0QYJKoZIhvcNAQcBoIIDwgSCA74xggO6MAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAMIwDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGVKEtg7c/MBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEELx2du71lAwrL2Yn2+NV2pAwHAIBBQIBAQQUNbdPrLbvu/mvxzlI+92xvXuC/a4wHgIBCAIBAQQWFhQyMDIwLTAyLTA1VDE1OjUzOjM5WjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
  public $transaction_id =>
  string(15) "530000529882397"
  public $request_type =>
  string(6) "mobile"
  public $notification_type =>
  NULL
  public $email =>
  string(27) "agustincrespo84@outlook.com"
  public $brand =>
  string(6) "drumeo"
  public $valid =>
  int(1)
  public $validation_error =>
  NULL
  public $payment_id =>
  NULL
  public $subscription_id =>
  int(74005)
  public $raw_receipt_response =>
  string(23515) "O:42:"ReceiptValidator\\iTunes\\ProductionResponse":13:{s:14:"\000*\000result_code";i:0;s:12:"\000*\000bundle_id";s:23:"com.drumeo.DrumeoMobile";s:14:"\000*\000app_item_id";i:1460388277;s:25:"\000*\000original_purchase_date";O:13:"Carbon\\Carbon":3:{s:4:"date";s:26:"2020-02-05 15:51:43.000000";s:13:"timezone_type";i:1;s:8:"timezone";s:6:"+00:00";}s:15:"\000*\000request_date";O:13:"Carbon\\Carbon":3:{s:4:"date";s:26:"2020-02-05 15:53:40.000000";s:13:"timezone_type";i:1;s:8:"timezone";s:6:"+00:00";}s:24:"\000"...
  public $created_at =>
  string(19) "2020-02-05 15:53:40"
  public $updated_at =>
  string(19) "2020-02-05 15:53:41"
}
/app/ecommerce/src/Commands/FixSerializeErrorInAppPurchaseTables.php:127:
class ReceiptValidator\iTunes\ProductionResponse#2236 (13) {
  protected $result_code =>
  int(0)
  protected $bundle_id =>
  string(23) "com.drumeo.DrumeoMobile"
  protected $app_item_id =>
  int(1460388277)
  protected $original_purchase_date =>
  class Carbon\Carbon#2848 (3) {
    public $date =>
    string(26) "2020-02-05 15:51:43.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $request_date =>
  class Carbon\Carbon#2829 (3) {
    public $date =>
    string(26) "2020-02-05 15:53:40.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $receipt_creation_date =>
  class Carbon\Carbon#2828 (3) {
    public $date =>
    string(26) "2020-02-05 15:53:39.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $receipt =>
  array(18) {
    'receipt_type' =>
    string(10) "Production"
    'adam_id' =>
    int(1460388277)
    'app_item_id' =>
    int(1460388277)
    'bundle_id' =>
    string(23) "com.drumeo.DrumeoMobile"
    'application_version' =>
    string(7) "0.0.245"
    'download_id' =>
    int(93051230074687)
    'version_external_identifier' =>
    int(834486669)
    'receipt_creation_date' =>
    string(27) "2020-02-05 15:53:39 Etc/GMT"
    'receipt_creation_date_ms' =>
    string(13) "1580918019000"
    'receipt_creation_date_pst' =>
    string(39) "2020-02-05 07:53:39 America/Los_Angeles"
    'request_date' =>
    string(27) "2020-02-05 15:53:40 Etc/GMT"
    'request_date_ms' =>
    string(13) "1580918020423"
    'request_date_pst' =>
    string(39) "2020-02-05 07:53:40 America/Los_Angeles"
    'original_purchase_date' =>
    string(27) "2020-02-05 15:51:43 Etc/GMT"
    'original_purchase_date_ms' =>
    string(13) "1580917903000"
    'original_purchase_date_pst' =>
    string(39) "2020-02-05 07:51:43 America/Los_Angeles"
    'original_application_version' =>
    string(7) "0.0.245"
    'in_app' =>
    array(1) {
      [0] =>
      array(16) {
        ...
      }
    }
  }
  protected $latest_receipt =>
  string(6880) "MIIUIgYJKoZIhvcNAQcCoIIUEzCCFA8CAQExCzAJBgUrDgMCGgUAMIIDwwYJKoZIhvcNAQcBoIIDtASCA7AxggOsMAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAMIwDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGVKEtg7c/MBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEELx2du71lAwrL2Yn2+NV2pAwHAIBBQIBAQQUNbdPrLbvu/mvxzlI+92xvXuC/a4wHgIBCAIBAQQWFhQyMDIwLTAyLTA1VDE1OjUzOjM5WjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
  protected $latest_receipt_info =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PurchaseItem#2841 (13) {
      protected $quantity =>
      int(1)
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $web_order_line_item_id =>
      string(15) "530000189146218"
      protected $transaction_id =>
      string(15) "530000529882397"
      protected $original_transaction_id =>
      string(15) "530000529882397"
      protected $purchase_date =>
      class Carbon\Carbon#2832 (3) {
        ...
      }
      protected $original_purchase_date =>
      class Carbon\Carbon#2838 (3) {
        ...
      }
      protected $expires_date =>
      class Carbon\Carbon#2840 (3) {
        ...
      }
      protected $cancellation_date =>
      NULL
      protected $is_trial_period =>
      bool(true)
      protected $is_in_intro_offer_period =>
      bool(false)
      protected $promotional_offer_id =>
      NULL
      protected $raw_data =>
      array(17) {
        ...
      }
    }
  }
  protected $purchases =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PurchaseItem#2845 (13) {
      protected $quantity =>
      int(1)
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $web_order_line_item_id =>
      string(15) "530000189146218"
      protected $transaction_id =>
      string(15) "530000529882397"
      protected $original_transaction_id =>
      string(15) "530000529882397"
      protected $purchase_date =>
      class Carbon\Carbon#2831 (3) {
        ...
      }
      protected $original_purchase_date =>
      class Carbon\Carbon#2842 (3) {
        ...
      }
      protected $expires_date =>
      class Carbon\Carbon#2844 (3) {
        ...
      }
      protected $cancellation_date =>
      NULL
      protected $is_trial_period =>
      bool(true)
      protected $is_in_intro_offer_period =>
      bool(false)
      protected $promotional_offer_id =>
      NULL
      protected $raw_data =>
      array(16) {
        ...
      }
    }
  }
  protected $pending_renewal_info =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PendingRenewalInfo#2827 (7) {
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $auto_renew_product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $original_transaction_id =>
      string(15) "530000529882397"
      protected $auto_renew_status =>
      bool(true)
      protected $expiration_intent =>
      NULL
      protected $is_in_billing_retry_period =>
      NULL
      protected $raw_data =>
      array(4) {
        ...
      }
    }
  }
  protected $raw_data =>
  array(6) {
    'status' =>
    int(0)
    'environment' =>
    string(10) "Production"
    'receipt' =>
    array(18) {
      'receipt_type' =>
      string(10) "Production"
      'adam_id' =>
      int(1460388277)
      'app_item_id' =>
      int(1460388277)
      'bundle_id' =>
      string(23) "com.drumeo.DrumeoMobile"
      'application_version' =>
      string(7) "0.0.245"
      'download_id' =>
      int(93051230074687)
      'version_external_identifier' =>
      int(834486669)
      'receipt_creation_date' =>
      string(27) "2020-02-05 15:53:39 Etc/GMT"
      'receipt_creation_date_ms' =>
      string(13) "1580918019000"
      'receipt_creation_date_pst' =>
      string(39) "2020-02-05 07:53:39 America/Los_Angeles"
      'request_date' =>
      string(27) "2020-02-05 15:53:40 Etc/GMT"
      'request_date_ms' =>
      string(13) "1580918020423"
      'request_date_pst' =>
      string(39) "2020-02-05 07:53:40 America/Los_Angeles"
      'original_purchase_date' =>
      string(27) "2020-02-05 15:51:43 Etc/GMT"
      'original_purchase_date_ms' =>
      string(13) "1580917903000"
      'original_purchase_date_pst' =>
      string(39) "2020-02-05 07:51:43 America/Los_Angeles"
      'original_application_version' =>
      string(7) "0.0.245"
      'in_app' =>
      array(1) {
        ...
      }
    }
    'latest_receipt_info' =>
    array(1) {
      [0] =>
      array(17) {
        ...
      }
    }
    'latest_receipt' =>
    string(6880) "MIIUIgYJKoZIhvcNAQcCoIIUEzCCFA8CAQExCzAJBgUrDgMCGgUAMIIDwwYJKoZIhvcNAQcBoIIDtASCA7AxggOsMAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAMIwDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGVKEtg7c/MBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEELx2du71lAwrL2Yn2+NV2pAwHAIBBQIBAQQUNbdPrLbvu/mvxzlI+92xvXuC/a4wHgIBCAIBAQQWFhQyMDIwLTAyLTA1VDE1OjUzOjM5WjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
    'pending_renewal_info' =>
    array(1) {
      [0] =>
      array(4) {
        ...
      }
    }
  }
  protected $is_retryable =>
  bool(false)
}
---------------------------------------------------------
/app/ecommerce/src/Commands/FixSerializeErrorInAppPurchaseTables.php:126:
class stdClass#2819 (14) {
  public $id =>
  int(563)
  public $receipt =>
  string(6896) "MIIUMAYJKoZIhvcNAQcCoIIUITCCFB0CAQExCzAJBgUrDgMCGgUAMIID0QYJKoZIhvcNAQcBoIIDwgSCA74xggO6MAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAMIwDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGWEMD7S95MBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEH75oOpwksx3haK4/ZrkbXwwHAIBBQIBAQQUk8G7f4dPLlLkHO6NGdu6jnDhFuIwHgIBCAIBAQQWFhQyMDIwLTAyLTA1VDE2OjMwOjIzWjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
  public $transaction_id =>
  string(15) "570000457635656"
  public $request_type =>
  string(6) "mobile"
  public $notification_type =>
  NULL
  public $email =>
  string(24) "ruby.silva2006@gmail.com"
  public $brand =>
  string(6) "drumeo"
  public $valid =>
  int(1)
  public $validation_error =>
  NULL
  public $payment_id =>
  NULL
  public $subscription_id =>
  int(74009)
  public $raw_receipt_response =>
  string(23523) "O:42:"ReceiptValidator\\iTunes\\ProductionResponse":13:{s:14:"\000*\000result_code";i:0;s:12:"\000*\000bundle_id";s:23:"com.drumeo.DrumeoMobile";s:14:"\000*\000app_item_id";i:1460388277;s:25:"\000*\000original_purchase_date";O:13:"Carbon\\Carbon":3:{s:4:"date";s:26:"2020-02-05 16:23:21.000000";s:13:"timezone_type";i:1;s:8:"timezone";s:6:"+00:00";}s:15:"\000*\000request_date";O:13:"Carbon\\Carbon":3:{s:4:"date";s:26:"2020-02-05 16:30:25.000000";s:13:"timezone_type";i:1;s:8:"timezone";s:6:"+00:00";}s:24:"\000"...
  public $created_at =>
  string(19) "2020-02-05 16:30:24"
  public $updated_at =>
  string(19) "2020-02-05 16:30:25"
}
/app/ecommerce/src/Commands/FixSerializeErrorInAppPurchaseTables.php:127:
class ReceiptValidator\iTunes\ProductionResponse#2236 (13) {
  protected $result_code =>
  int(0)
  protected $bundle_id =>
  string(23) "com.drumeo.DrumeoMobile"
  protected $app_item_id =>
  int(1460388277)
  protected $original_purchase_date =>
  class Carbon\Carbon#2827 (3) {
    public $date =>
    string(26) "2020-02-05 16:23:21.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $request_date =>
  class Carbon\Carbon#2845 (3) {
    public $date =>
    string(26) "2020-02-05 16:30:25.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $receipt_creation_date =>
  class Carbon\Carbon#2844 (3) {
    public $date =>
    string(26) "2020-02-05 16:30:23.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $receipt =>
  array(18) {
    'receipt_type' =>
    string(10) "Production"
    'adam_id' =>
    int(1460388277)
    'app_item_id' =>
    int(1460388277)
    'bundle_id' =>
    string(23) "com.drumeo.DrumeoMobile"
    'application_version' =>
    string(7) "0.0.245"
    'download_id' =>
    int(97044851928953)
    'version_external_identifier' =>
    int(834486669)
    'receipt_creation_date' =>
    string(27) "2020-02-05 16:30:23 Etc/GMT"
    'receipt_creation_date_ms' =>
    string(13) "1580920223000"
    'receipt_creation_date_pst' =>
    string(39) "2020-02-05 08:30:23 America/Los_Angeles"
    'request_date' =>
    string(27) "2020-02-05 16:30:24 Etc/GMT"
    'request_date_ms' =>
    string(13) "1580920224870"
    'request_date_pst' =>
    string(39) "2020-02-05 08:30:24 America/Los_Angeles"
    'original_purchase_date' =>
    string(27) "2020-02-05 16:23:21 Etc/GMT"
    'original_purchase_date_ms' =>
    string(13) "1580919801000"
    'original_purchase_date_pst' =>
    string(39) "2020-02-05 08:23:21 America/Los_Angeles"
    'original_application_version' =>
    string(7) "0.0.245"
    'in_app' =>
    array(1) {
      [0] =>
      array(16) {
        ...
      }
    }
  }
  protected $latest_receipt =>
  string(6884) "MIIUJwYJKoZIhvcNAQcCoIIUGDCCFBQCAQExCzAJBgUrDgMCGgUAMIIDyAYJKoZIhvcNAQcBoIIDuQSCA7UxggOxMAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAMIwDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGWEMD7S95MBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEH75oOpwksx3haK4/ZrkbXwwHAIBBQIBAQQUk8G7f4dPLlLkHO6NGdu6jnDhFuIwHgIBCAIBAQQWFhQyMDIwLTAyLTA1VDE2OjMwOjIzWjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
  protected $latest_receipt_info =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PurchaseItem#2842 (13) {
      protected $quantity =>
      int(1)
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $web_order_line_item_id =>
      string(15) "570000159140214"
      protected $transaction_id =>
      string(15) "570000457635656"
      protected $original_transaction_id =>
      string(15) "570000457635656"
      protected $purchase_date =>
      class Carbon\Carbon#2831 (3) {
        ...
      }
      protected $original_purchase_date =>
      class Carbon\Carbon#2841 (3) {
        ...
      }
      protected $expires_date =>
      class Carbon\Carbon#2840 (3) {
        ...
      }
      protected $cancellation_date =>
      NULL
      protected $is_trial_period =>
      bool(true)
      protected $is_in_intro_offer_period =>
      bool(false)
      protected $promotional_offer_id =>
      NULL
      protected $raw_data =>
      array(17) {
        ...
      }
    }
  }
  protected $purchases =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PurchaseItem#2838 (13) {
      protected $quantity =>
      int(1)
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $web_order_line_item_id =>
      string(15) "570000159140214"
      protected $transaction_id =>
      string(15) "570000457635656"
      protected $original_transaction_id =>
      string(15) "570000457635656"
      protected $purchase_date =>
      class Carbon\Carbon#2832 (3) {
        ...
      }
      protected $original_purchase_date =>
      class Carbon\Carbon#2828 (3) {
        ...
      }
      protected $expires_date =>
      class Carbon\Carbon#2829 (3) {
        ...
      }
      protected $cancellation_date =>
      NULL
      protected $is_trial_period =>
      bool(true)
      protected $is_in_intro_offer_period =>
      bool(false)
      protected $promotional_offer_id =>
      NULL
      protected $raw_data =>
      array(16) {
        ...
      }
    }
  }
  protected $pending_renewal_info =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PendingRenewalInfo#2848 (7) {
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $auto_renew_product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $original_transaction_id =>
      string(15) "570000457635656"
      protected $auto_renew_status =>
      bool(true)
      protected $expiration_intent =>
      NULL
      protected $is_in_billing_retry_period =>
      NULL
      protected $raw_data =>
      array(4) {
        ...
      }
    }
  }
  protected $raw_data =>
  array(6) {
    'status' =>
    int(0)
    'environment' =>
    string(10) "Production"
    'receipt' =>
    array(18) {
      'receipt_type' =>
      string(10) "Production"
      'adam_id' =>
      int(1460388277)
      'app_item_id' =>
      int(1460388277)
      'bundle_id' =>
      string(23) "com.drumeo.DrumeoMobile"
      'application_version' =>
      string(7) "0.0.245"
      'download_id' =>
      int(97044851928953)
      'version_external_identifier' =>
      int(834486669)
      'receipt_creation_date' =>
      string(27) "2020-02-05 16:30:23 Etc/GMT"
      'receipt_creation_date_ms' =>
      string(13) "1580920223000"
      'receipt_creation_date_pst' =>
      string(39) "2020-02-05 08:30:23 America/Los_Angeles"
      'request_date' =>
      string(27) "2020-02-05 16:30:24 Etc/GMT"
      'request_date_ms' =>
      string(13) "1580920224870"
      'request_date_pst' =>
      string(39) "2020-02-05 08:30:24 America/Los_Angeles"
      'original_purchase_date' =>
      string(27) "2020-02-05 16:23:21 Etc/GMT"
      'original_purchase_date_ms' =>
      string(13) "1580919801000"
      'original_purchase_date_pst' =>
      string(39) "2020-02-05 08:23:21 America/Los_Angeles"
      'original_application_version' =>
      string(7) "0.0.245"
      'in_app' =>
      array(1) {
        ...
      }
    }
    'latest_receipt_info' =>
    array(1) {
      [0] =>
      array(17) {
        ...
      }
    }
    'latest_receipt' =>
    string(6884) "MIIUJwYJKoZIhvcNAQcCoIIUGDCCFBQCAQExCzAJBgUrDgMCGgUAMIIDyAYJKoZIhvcNAQcBoIIDuQSCA7UxggOxMAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAMIwDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGWEMD7S95MBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEH75oOpwksx3haK4/ZrkbXwwHAIBBQIBAQQUk8G7f4dPLlLkHO6NGdu6jnDhFuIwHgIBCAIBAQQWFhQyMDIwLTAyLTA1VDE2OjMwOjIzWjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
    'pending_renewal_info' =>
    array(1) {
      [0] =>
      array(4) {
        ...
      }
    }
  }
  protected $is_retryable =>
  bool(false)
}
---------------------------------------------------------
/app/ecommerce/src/Commands/FixSerializeErrorInAppPurchaseTables.php:126:
class stdClass#2820 (14) {
  public $id =>
  int(564)
  public $receipt =>
  string(6884) "MIIUJgYJKoZIhvcNAQcCoIIUFzCCFBMCAQExCzAJBgUrDgMCGgUAMIIDxwYJKoZIhvcNAQcBoIIDuASCA7QxggOwMAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAIswDQIBDQIBAQQFAgMB/P0wDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGQYptPdM4MBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEFBPeSq9IWULzp2vtB2y1A0wHAIBBQIBAQQUB1y7bbuIeR8dAD3QDYAxOYAq3f8wHgIBCAIBAQQWFhQyMDIwLTAyLTA1VDE5OjQxOjMxWjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
  public $transaction_id =>
  string(15) "320000641866809"
  public $request_type =>
  string(6) "mobile"
  public $notification_type =>
  NULL
  public $email =>
  string(27) "manus.engelbrecht@gmail.com"
  public $brand =>
  string(6) "drumeo"
  public $valid =>
  int(1)
  public $validation_error =>
  NULL
  public $payment_id =>
  NULL
  public $subscription_id =>
  int(74020)
  public $raw_receipt_response =>
  string(23515) "O:42:"ReceiptValidator\\iTunes\\ProductionResponse":13:{s:14:"\000*\000result_code";i:0;s:12:"\000*\000bundle_id";s:23:"com.drumeo.DrumeoMobile";s:14:"\000*\000app_item_id";i:1460388277;s:25:"\000*\000original_purchase_date";O:13:"Carbon\\Carbon":3:{s:4:"date";s:26:"2020-02-05 19:32:08.000000";s:13:"timezone_type";i:1;s:8:"timezone";s:6:"+00:00";}s:15:"\000*\000request_date";O:13:"Carbon\\Carbon":3:{s:4:"date";s:26:"2020-02-05 19:41:34.000000";s:13:"timezone_type";i:1;s:8:"timezone";s:6:"+00:00";}s:24:"\000"...
  public $created_at =>
  string(19) "2020-02-05 19:41:33"
  public $updated_at =>
  string(19) "2020-02-05 19:41:35"
}
/app/ecommerce/src/Commands/FixSerializeErrorInAppPurchaseTables.php:127:
class ReceiptValidator\iTunes\ProductionResponse#2236 (13) {
  protected $result_code =>
  int(0)
  protected $bundle_id =>
  string(23) "com.drumeo.DrumeoMobile"
  protected $app_item_id =>
  int(1460388277)
  protected $original_purchase_date =>
  class Carbon\Carbon#2848 (3) {
    public $date =>
    string(26) "2020-02-05 19:32:08.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $request_date =>
  class Carbon\Carbon#2838 (3) {
    public $date =>
    string(26) "2020-02-05 19:41:34.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $receipt_creation_date =>
  class Carbon\Carbon#2829 (3) {
    public $date =>
    string(26) "2020-02-05 19:41:31.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $receipt =>
  array(18) {
    'receipt_type' =>
    string(10) "Production"
    'adam_id' =>
    int(1460388277)
    'app_item_id' =>
    int(1460388277)
    'bundle_id' =>
    string(23) "com.drumeo.DrumeoMobile"
    'application_version' =>
    string(7) "0.0.245"
    'download_id' =>
    int(72062794060600)
    'version_external_identifier' =>
    int(834486669)
    'receipt_creation_date' =>
    string(27) "2020-02-05 19:41:31 Etc/GMT"
    'receipt_creation_date_ms' =>
    string(13) "1580931691000"
    'receipt_creation_date_pst' =>
    string(39) "2020-02-05 11:41:31 America/Los_Angeles"
    'request_date' =>
    string(27) "2020-02-05 19:41:34 Etc/GMT"
    'request_date_ms' =>
    string(13) "1580931694238"
    'request_date_pst' =>
    string(39) "2020-02-05 11:41:34 America/Los_Angeles"
    'original_purchase_date' =>
    string(27) "2020-02-05 19:32:08 Etc/GMT"
    'original_purchase_date_ms' =>
    string(13) "1580931128000"
    'original_purchase_date_pst' =>
    string(39) "2020-02-05 11:32:08 America/Los_Angeles"
    'original_application_version' =>
    string(7) "0.0.245"
    'in_app' =>
    array(1) {
      [0] =>
      array(16) {
        ...
      }
    }
  }
  protected $latest_receipt =>
  string(6880) "MIIUIwYJKoZIhvcNAQcCoIIUFDCCFBACAQExCzAJBgUrDgMCGgUAMIIDxAYJKoZIhvcNAQcBoIIDtQSCA7ExggOtMAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAIswDQIBDQIBAQQFAgMB/P0wDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGQYptPdM4MBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEFBPeSq9IWULzp2vtB2y1A0wHAIBBQIBAQQUB1y7bbuIeR8dAD3QDYAxOYAq3f8wHgIBCAIBAQQWFhQyMDIwLTAyLTA1VDE5OjQxOjMxWjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
  protected $latest_receipt_info =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PurchaseItem#2828 (13) {
      protected $quantity =>
      int(1)
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $web_order_line_item_id =>
      string(15) "320000228998867"
      protected $transaction_id =>
      string(15) "320000641866809"
      protected $original_transaction_id =>
      string(15) "320000641866809"
      protected $purchase_date =>
      class Carbon\Carbon#2832 (3) {
        ...
      }
      protected $original_purchase_date =>
      class Carbon\Carbon#2842 (3) {
        ...
      }
      protected $expires_date =>
      class Carbon\Carbon#2840 (3) {
        ...
      }
      protected $cancellation_date =>
      NULL
      protected $is_trial_period =>
      bool(true)
      protected $is_in_intro_offer_period =>
      bool(false)
      protected $promotional_offer_id =>
      NULL
      protected $raw_data =>
      array(17) {
        ...
      }
    }
  }
  protected $purchases =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PurchaseItem#2841 (13) {
      protected $quantity =>
      int(1)
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $web_order_line_item_id =>
      string(15) "320000228998867"
      protected $transaction_id =>
      string(15) "320000641866809"
      protected $original_transaction_id =>
      string(15) "320000641866809"
      protected $purchase_date =>
      class Carbon\Carbon#2831 (3) {
        ...
      }
      protected $original_purchase_date =>
      class Carbon\Carbon#2844 (3) {
        ...
      }
      protected $expires_date =>
      class Carbon\Carbon#2845 (3) {
        ...
      }
      protected $cancellation_date =>
      NULL
      protected $is_trial_period =>
      bool(true)
      protected $is_in_intro_offer_period =>
      bool(false)
      protected $promotional_offer_id =>
      NULL
      protected $raw_data =>
      array(16) {
        ...
      }
    }
  }
  protected $pending_renewal_info =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PendingRenewalInfo#2827 (7) {
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $auto_renew_product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $original_transaction_id =>
      string(15) "320000641866809"
      protected $auto_renew_status =>
      bool(true)
      protected $expiration_intent =>
      NULL
      protected $is_in_billing_retry_period =>
      NULL
      protected $raw_data =>
      array(4) {
        ...
      }
    }
  }
  protected $raw_data =>
  array(6) {
    'status' =>
    int(0)
    'environment' =>
    string(10) "Production"
    'receipt' =>
    array(18) {
      'receipt_type' =>
      string(10) "Production"
      'adam_id' =>
      int(1460388277)
      'app_item_id' =>
      int(1460388277)
      'bundle_id' =>
      string(23) "com.drumeo.DrumeoMobile"
      'application_version' =>
      string(7) "0.0.245"
      'download_id' =>
      int(72062794060600)
      'version_external_identifier' =>
      int(834486669)
      'receipt_creation_date' =>
      string(27) "2020-02-05 19:41:31 Etc/GMT"
      'receipt_creation_date_ms' =>
      string(13) "1580931691000"
      'receipt_creation_date_pst' =>
      string(39) "2020-02-05 11:41:31 America/Los_Angeles"
      'request_date' =>
      string(27) "2020-02-05 19:41:34 Etc/GMT"
      'request_date_ms' =>
      string(13) "1580931694238"
      'request_date_pst' =>
      string(39) "2020-02-05 11:41:34 America/Los_Angeles"
      'original_purchase_date' =>
      string(27) "2020-02-05 19:32:08 Etc/GMT"
      'original_purchase_date_ms' =>
      string(13) "1580931128000"
      'original_purchase_date_pst' =>
      string(39) "2020-02-05 11:32:08 America/Los_Angeles"
      'original_application_version' =>
      string(7) "0.0.245"
      'in_app' =>
      array(1) {
        ...
      }
    }
    'latest_receipt_info' =>
    array(1) {
      [0] =>
      array(17) {
        ...
      }
    }
    'latest_receipt' =>
    string(6880) "MIIUIwYJKoZIhvcNAQcCoIIUFDCCFBACAQExCzAJBgUrDgMCGgUAMIIDxAYJKoZIhvcNAQcBoIIDtQSCA7ExggOtMAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAIswDQIBDQIBAQQFAgMB/P0wDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGQYptPdM4MBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEFBPeSq9IWULzp2vtB2y1A0wHAIBBQIBAQQUB1y7bbuIeR8dAD3QDYAxOYAq3f8wHgIBCAIBAQQWFhQyMDIwLTAyLTA1VDE5OjQxOjMxWjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
    'pending_renewal_info' =>
    array(1) {
      [0] =>
      array(4) {
        ...
      }
    }
  }
  protected $is_retryable =>
  bool(false)
}
---------------------------------------------------------
/app/ecommerce/src/Commands/FixSerializeErrorInAppPurchaseTables.php:126:
class stdClass#2821 (14) {
  public $id =>
  int(565)
  public $receipt =>
  string(6880) "MIIUIwYJKoZIhvcNAQcCoIIUFDCCFBACAQExCzAJBgUrDgMCGgUAMIIDxAYJKoZIhvcNAQcBoIIDtQSCA7ExggOtMAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAKwwDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGGm/pLLDoMBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEKPn5b2xjvIa+xwE9T4A7JswHAIBBQIBAQQUE4R4pvNWlPNtlkETL3VLPMgYN+swHgIBCAIBAQQWFhQyMDIwLTAyLTA1VDIwOjI0OjM2WjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
  public $transaction_id =>
  string(14) "90000697104183"
  public $request_type =>
  string(6) "mobile"
  public $notification_type =>
  NULL
  public $email =>
  string(21) "tonydrums80@gmail.com"
  public $brand =>
  string(6) "drumeo"
  public $valid =>
  int(1)
  public $validation_error =>
  NULL
  public $payment_id =>
  NULL
  public $subscription_id =>
  int(74022)
  public $raw_receipt_response =>
  string(23406) "O:42:"ReceiptValidator\\iTunes\\ProductionResponse":13:{s:14:"\000*\000result_code";i:0;s:12:"\000*\000bundle_id";s:23:"com.drumeo.DrumeoMobile";s:14:"\000*\000app_item_id";i:1460388277;s:25:"\000*\000original_purchase_date";O:13:"Carbon\\Carbon":3:{s:4:"date";s:26:"2020-02-05 20:19:51.000000";s:13:"timezone_type";i:1;s:8:"timezone";s:6:"+00:00";}s:15:"\000*\000request_date";O:13:"Carbon\\Carbon":3:{s:4:"date";s:26:"2020-02-05 20:24:40.000000";s:13:"timezone_type";i:1;s:8:"timezone";s:6:"+00:00";}s:24:"\000"...
  public $created_at =>
  string(19) "2020-02-05 20:24:39"
  public $updated_at =>
  string(19) "2020-02-05 20:24:40"
}
/app/ecommerce/src/Commands/FixSerializeErrorInAppPurchaseTables.php:127:
class ReceiptValidator\iTunes\ProductionResponse#2236 (13) {
  protected $result_code =>
  int(0)
  protected $bundle_id =>
  string(23) "com.drumeo.DrumeoMobile"
  protected $app_item_id =>
  int(1460388277)
  protected $original_purchase_date =>
  class Carbon\Carbon#2827 (3) {
    public $date =>
    string(26) "2020-02-05 20:19:51.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $request_date =>
  class Carbon\Carbon#2841 (3) {
    public $date =>
    string(26) "2020-02-05 20:24:40.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $receipt_creation_date =>
  class Carbon\Carbon#2845 (3) {
    public $date =>
    string(26) "2020-02-05 20:24:36.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $receipt =>
  array(18) {
    'receipt_type' =>
    string(10) "Production"
    'adam_id' =>
    int(1460388277)
    'app_item_id' =>
    int(1460388277)
    'bundle_id' =>
    string(23) "com.drumeo.DrumeoMobile"
    'application_version' =>
    string(7) "0.0.245"
    'download_id' =>
    int(29067955712232)
    'version_external_identifier' =>
    int(834486669)
    'receipt_creation_date' =>
    string(27) "2020-02-05 20:24:36 Etc/GMT"
    'receipt_creation_date_ms' =>
    string(13) "1580934276000"
    'receipt_creation_date_pst' =>
    string(39) "2020-02-05 12:24:36 America/Los_Angeles"
    'request_date' =>
    string(27) "2020-02-05 20:24:39 Etc/GMT"
    'request_date_ms' =>
    string(13) "1580934279605"
    'request_date_pst' =>
    string(39) "2020-02-05 12:24:39 America/Los_Angeles"
    'original_purchase_date' =>
    string(27) "2020-02-05 20:19:51 Etc/GMT"
    'original_purchase_date_ms' =>
    string(13) "1580933991000"
    'original_purchase_date_pst' =>
    string(39) "2020-02-05 12:19:51 America/Los_Angeles"
    'original_application_version' =>
    string(7) "0.0.245"
    'in_app' =>
    array(1) {
      [0] =>
      array(16) {
        ...
      }
    }
  }
  protected $latest_receipt =>
  string(6844) "MIIUCAYJKoZIhvcNAQcCoIIT+TCCE/UCAQExCzAJBgUrDgMCGgUAMIIDqQYJKoZIhvcNAQcBoIIDmgSCA5YxggOSMAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAKwwDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGGm/pLLDoMBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEKPn5b2xjvIa+xwE9T4A7JswHAIBBQIBAQQUE4R4pvNWlPNtlkETL3VLPMgYN+swHgIBCAIBAQQWFhQyMDIwLTAyLTA1VDIwOjI0OjM2WjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
  protected $latest_receipt_info =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PurchaseItem#2844 (13) {
      protected $quantity =>
      int(1)
      protected $product_id =>
      string(24) "drumeo_app_1_year_member"
      protected $web_order_line_item_id =>
      string(14) "90000247439870"
      protected $transaction_id =>
      string(14) "90000697104183"
      protected $original_transaction_id =>
      string(14) "90000697104183"
      protected $purchase_date =>
      class Carbon\Carbon#2831 (3) {
        ...
      }
      protected $original_purchase_date =>
      class Carbon\Carbon#2828 (3) {
        ...
      }
      protected $expires_date =>
      class Carbon\Carbon#2840 (3) {
        ...
      }
      protected $cancellation_date =>
      NULL
      protected $is_trial_period =>
      bool(true)
      protected $is_in_intro_offer_period =>
      bool(false)
      protected $promotional_offer_id =>
      NULL
      protected $raw_data =>
      array(17) {
        ...
      }
    }
  }
  protected $purchases =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PurchaseItem#2842 (13) {
      protected $quantity =>
      int(1)
      protected $product_id =>
      string(24) "drumeo_app_1_year_member"
      protected $web_order_line_item_id =>
      string(14) "90000247439870"
      protected $transaction_id =>
      string(14) "90000697104183"
      protected $original_transaction_id =>
      string(14) "90000697104183"
      protected $purchase_date =>
      class Carbon\Carbon#2832 (3) {
        ...
      }
      protected $original_purchase_date =>
      class Carbon\Carbon#2829 (3) {
        ...
      }
      protected $expires_date =>
      class Carbon\Carbon#2838 (3) {
        ...
      }
      protected $cancellation_date =>
      NULL
      protected $is_trial_period =>
      bool(true)
      protected $is_in_intro_offer_period =>
      bool(false)
      protected $promotional_offer_id =>
      NULL
      protected $raw_data =>
      array(16) {
        ...
      }
    }
  }
  protected $pending_renewal_info =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PendingRenewalInfo#2848 (7) {
      protected $product_id =>
      string(24) "drumeo_app_1_year_member"
      protected $auto_renew_product_id =>
      string(24) "drumeo_app_1_year_member"
      protected $original_transaction_id =>
      string(14) "90000697104183"
      protected $auto_renew_status =>
      bool(true)
      protected $expiration_intent =>
      NULL
      protected $is_in_billing_retry_period =>
      NULL
      protected $raw_data =>
      array(4) {
        ...
      }
    }
  }
  protected $raw_data =>
  array(6) {
    'status' =>
    int(0)
    'environment' =>
    string(10) "Production"
    'receipt' =>
    array(18) {
      'receipt_type' =>
      string(10) "Production"
      'adam_id' =>
      int(1460388277)
      'app_item_id' =>
      int(1460388277)
      'bundle_id' =>
      string(23) "com.drumeo.DrumeoMobile"
      'application_version' =>
      string(7) "0.0.245"
      'download_id' =>
      int(29067955712232)
      'version_external_identifier' =>
      int(834486669)
      'receipt_creation_date' =>
      string(27) "2020-02-05 20:24:36 Etc/GMT"
      'receipt_creation_date_ms' =>
      string(13) "1580934276000"
      'receipt_creation_date_pst' =>
      string(39) "2020-02-05 12:24:36 America/Los_Angeles"
      'request_date' =>
      string(27) "2020-02-05 20:24:39 Etc/GMT"
      'request_date_ms' =>
      string(13) "1580934279605"
      'request_date_pst' =>
      string(39) "2020-02-05 12:24:39 America/Los_Angeles"
      'original_purchase_date' =>
      string(27) "2020-02-05 20:19:51 Etc/GMT"
      'original_purchase_date_ms' =>
      string(13) "1580933991000"
      'original_purchase_date_pst' =>
      string(39) "2020-02-05 12:19:51 America/Los_Angeles"
      'original_application_version' =>
      string(7) "0.0.245"
      'in_app' =>
      array(1) {
        ...
      }
    }
    'latest_receipt_info' =>
    array(1) {
      [0] =>
      array(17) {
        ...
      }
    }
    'latest_receipt' =>
    string(6844) "MIIUCAYJKoZIhvcNAQcCoIIT+TCCE/UCAQExCzAJBgUrDgMCGgUAMIIDqQYJKoZIhvcNAQcBoIIDmgSCA5YxggOSMAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAKwwDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGGm/pLLDoMBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEKPn5b2xjvIa+xwE9T4A7JswHAIBBQIBAQQUE4R4pvNWlPNtlkETL3VLPMgYN+swHgIBCAIBAQQWFhQyMDIwLTAyLTA1VDIwOjI0OjM2WjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
    'pending_renewal_info' =>
    array(1) {
      [0] =>
      array(4) {
        ...
      }
    }
  }
  protected $is_retryable =>
  bool(false)
}
---------------------------------------------------------
/app/ecommerce/src/Commands/FixSerializeErrorInAppPurchaseTables.php:126:
class stdClass#2822 (14) {
  public $id =>
  int(566)
  public $receipt =>
  string(6896) "MIIUMAYJKoZIhvcNAQcCoIIUITCCFB0CAQExCzAJBgUrDgMCGgUAMIID0QYJKoZIhvcNAQcBoIIDwgSCA74xggO6MAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAMIwDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGUBZWKCIbMBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEECkH4w060JPnOI7hk7S2uUAwHAIBBQIBAQQUoxf3fL0SQq6oVlOoqryjJfIBhBEwHgIBCAIBAQQWFhQyMDIwLTAyLTA1VDIyOjExOjQwWjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
  public $transaction_id =>
  string(15) "480000562655805"
  public $request_type =>
  string(6) "mobile"
  public $notification_type =>
  NULL
  public $email =>
  string(23) "swilliams1309@gmail.com"
  public $brand =>
  string(6) "drumeo"
  public $valid =>
  int(1)
  public $validation_error =>
  NULL
  public $payment_id =>
  NULL
  public $subscription_id =>
  int(74025)
  public $raw_receipt_response =>
  string(23534) "O:42:"ReceiptValidator\\iTunes\\ProductionResponse":13:{s:14:"\000*\000result_code";i:0;s:12:"\000*\000bundle_id";s:23:"com.drumeo.DrumeoMobile";s:14:"\000*\000app_item_id";i:1460388277;s:25:"\000*\000original_purchase_date";O:13:"Carbon\\Carbon":3:{s:4:"date";s:26:"2020-02-05 22:09:46.000000";s:13:"timezone_type";i:1;s:8:"timezone";s:6:"+00:00";}s:15:"\000*\000request_date";O:13:"Carbon\\Carbon":3:{s:4:"date";s:26:"2020-02-05 22:11:42.000000";s:13:"timezone_type";i:1;s:8:"timezone";s:6:"+00:00";}s:24:"\000"...
  public $created_at =>
  string(19) "2020-02-05 22:11:41"
  public $updated_at =>
  string(19) "2020-02-05 22:11:42"
}
/app/ecommerce/src/Commands/FixSerializeErrorInAppPurchaseTables.php:127:
class ReceiptValidator\iTunes\ProductionResponse#2236 (13) {
  protected $result_code =>
  int(0)
  protected $bundle_id =>
  string(23) "com.drumeo.DrumeoMobile"
  protected $app_item_id =>
  int(1460388277)
  protected $original_purchase_date =>
  class Carbon\Carbon#2848 (3) {
    public $date =>
    string(26) "2020-02-05 22:09:46.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $request_date =>
  class Carbon\Carbon#2842 (3) {
    public $date =>
    string(26) "2020-02-05 22:11:42.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $receipt_creation_date =>
  class Carbon\Carbon#2838 (3) {
    public $date =>
    string(26) "2020-02-05 22:11:40.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $receipt =>
  array(18) {
    'receipt_type' =>
    string(10) "Production"
    'adam_id' =>
    int(1460388277)
    'app_item_id' =>
    int(1460388277)
    'bundle_id' =>
    string(23) "com.drumeo.DrumeoMobile"
    'application_version' =>
    string(7) "0.0.245"
    'download_id' =>
    int(88056864973339)
    'version_external_identifier' =>
    int(834486669)
    'receipt_creation_date' =>
    string(27) "2020-02-05 22:11:40 Etc/GMT"
    'receipt_creation_date_ms' =>
    string(13) "1580940700000"
    'receipt_creation_date_pst' =>
    string(39) "2020-02-05 14:11:40 America/Los_Angeles"
    'request_date' =>
    string(27) "2020-02-05 22:11:41 Etc/GMT"
    'request_date_ms' =>
    string(13) "1580940701821"
    'request_date_pst' =>
    string(39) "2020-02-05 14:11:41 America/Los_Angeles"
    'original_purchase_date' =>
    string(27) "2020-02-05 22:09:46 Etc/GMT"
    'original_purchase_date_ms' =>
    string(13) "1580940586000"
    'original_purchase_date_pst' =>
    string(39) "2020-02-05 14:09:46 America/Los_Angeles"
    'original_application_version' =>
    string(7) "0.0.245"
    'in_app' =>
    array(1) {
      [0] =>
      array(16) {
        ...
      }
    }
  }
  protected $latest_receipt =>
  string(6896) "MIIUMAYJKoZIhvcNAQcCoIIUITCCFB0CAQExCzAJBgUrDgMCGgUAMIID0QYJKoZIhvcNAQcBoIIDwgSCA74xggO6MAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAMIwDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGUBZWKCIbMBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEECkH4w060JPnOI7hk7S2uUAwHAIBBQIBAQQUoxf3fL0SQq6oVlOoqryjJfIBhBEwHgIBCAIBAQQWFhQyMDIwLTAyLTA1VDIyOjExOjQwWjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
  protected $latest_receipt_info =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PurchaseItem#2829 (13) {
      protected $quantity =>
      int(1)
      protected $product_id =>
      string(24) "drumeo_app_1_year_member"
      protected $web_order_line_item_id =>
      string(15) "480000209127406"
      protected $transaction_id =>
      string(15) "480000562655805"
      protected $original_transaction_id =>
      string(15) "480000562655805"
      protected $purchase_date =>
      class Carbon\Carbon#2832 (3) {
        ...
      }
      protected $original_purchase_date =>
      class Carbon\Carbon#2844 (3) {
        ...
      }
      protected $expires_date =>
      class Carbon\Carbon#2840 (3) {
        ...
      }
      protected $cancellation_date =>
      NULL
      protected $is_trial_period =>
      bool(true)
      protected $is_in_intro_offer_period =>
      bool(false)
      protected $promotional_offer_id =>
      NULL
      protected $raw_data =>
      array(17) {
        ...
      }
    }
  }
  protected $purchases =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PurchaseItem#2828 (13) {
      protected $quantity =>
      int(1)
      protected $product_id =>
      string(24) "drumeo_app_1_year_member"
      protected $web_order_line_item_id =>
      string(15) "480000209127406"
      protected $transaction_id =>
      string(15) "480000562655805"
      protected $original_transaction_id =>
      string(15) "480000562655805"
      protected $purchase_date =>
      class Carbon\Carbon#2831 (3) {
        ...
      }
      protected $original_purchase_date =>
      class Carbon\Carbon#2845 (3) {
        ...
      }
      protected $expires_date =>
      class Carbon\Carbon#2841 (3) {
        ...
      }
      protected $cancellation_date =>
      NULL
      protected $is_trial_period =>
      bool(true)
      protected $is_in_intro_offer_period =>
      bool(false)
      protected $promotional_offer_id =>
      NULL
      protected $raw_data =>
      array(16) {
        ...
      }
    }
  }
  protected $pending_renewal_info =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PendingRenewalInfo#2827 (7) {
      protected $product_id =>
      string(24) "drumeo_app_1_year_member"
      protected $auto_renew_product_id =>
      string(24) "drumeo_app_1_year_member"
      protected $original_transaction_id =>
      string(15) "480000562655805"
      protected $auto_renew_status =>
      bool(true)
      protected $expiration_intent =>
      NULL
      protected $is_in_billing_retry_period =>
      NULL
      protected $raw_data =>
      array(4) {
        ...
      }
    }
  }
  protected $raw_data =>
  array(6) {
    'status' =>
    int(0)
    'environment' =>
    string(10) "Production"
    'receipt' =>
    array(18) {
      'receipt_type' =>
      string(10) "Production"
      'adam_id' =>
      int(1460388277)
      'app_item_id' =>
      int(1460388277)
      'bundle_id' =>
      string(23) "com.drumeo.DrumeoMobile"
      'application_version' =>
      string(7) "0.0.245"
      'download_id' =>
      int(88056864973339)
      'version_external_identifier' =>
      int(834486669)
      'receipt_creation_date' =>
      string(27) "2020-02-05 22:11:40 Etc/GMT"
      'receipt_creation_date_ms' =>
      string(13) "1580940700000"
      'receipt_creation_date_pst' =>
      string(39) "2020-02-05 14:11:40 America/Los_Angeles"
      'request_date' =>
      string(27) "2020-02-05 22:11:41 Etc/GMT"
      'request_date_ms' =>
      string(13) "1580940701821"
      'request_date_pst' =>
      string(39) "2020-02-05 14:11:41 America/Los_Angeles"
      'original_purchase_date' =>
      string(27) "2020-02-05 22:09:46 Etc/GMT"
      'original_purchase_date_ms' =>
      string(13) "1580940586000"
      'original_purchase_date_pst' =>
      string(39) "2020-02-05 14:09:46 America/Los_Angeles"
      'original_application_version' =>
      string(7) "0.0.245"
      'in_app' =>
      array(1) {
        ...
      }
    }
    'latest_receipt_info' =>
    array(1) {
      [0] =>
      array(17) {
        ...
      }
    }
    'latest_receipt' =>
    string(6896) "MIIUMAYJKoZIhvcNAQcCoIIUITCCFB0CAQExCzAJBgUrDgMCGgUAMIID0QYJKoZIhvcNAQcBoIIDwgSCA74xggO6MAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAMIwDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGUBZWKCIbMBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEECkH4w060JPnOI7hk7S2uUAwHAIBBQIBAQQUoxf3fL0SQq6oVlOoqryjJfIBhBEwHgIBCAIBAQQWFhQyMDIwLTAyLTA1VDIyOjExOjQwWjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
    'pending_renewal_info' =>
    array(1) {
      [0] =>
      array(4) {
        ...
      }
    }
  }
  protected $is_retryable =>
  bool(false)
}
---------------------------------------------------------
/app/ecommerce/src/Commands/FixSerializeErrorInAppPurchaseTables.php:126:
class stdClass#2823 (14) {
  public $id =>
  int(567)
  public $receipt =>
  string(5080) "ewoJInNpZ25hdHVyZSIgPSAiQXdBV3k0blpUaXRDUnhIRDdsYkMzUms0UlVhWklodlMvZ1czM1JUWDYzREszQUthbUFmdEM3U0VyRU1GUzBDSy9GRTRLT1B1TjdwNUh5SU9heEdMSjh2bHR0RllVbE1GNnJsbTJwQmdYdGNNSHRpaFNyTHRKUGFNeWE1cWx5TGthMUhUc0hBMjBxaDhZL0QwQk1tam5IWXN6YlMrOE5MS2h6QzRuOVdTNSsvcExjdkYxa0s0cDZwT2tBTWNnSVJXRkRGaHB6WE9Hd2Noc1RMcjRuazR3NnJ6VE5xUlRCcGFyOGptZkUxdWx6ZEtHYkVGUkxiaG96NnBFaEZNMkZDbFJlaXEvTUZ0TWV6Um5JN09aam5xMXgraUtiNUlQQlN3b1A2MnFiSmM1QUhLWGM0d09MTEtvY0hqa3VwbUphWFh2c3RnMXBWaTFEYXhsbUFmQWt5aUc5Y0FBQVdBTUlJRmZEQ0NCR1NnQXdJQkFn"...
  public $transaction_id =>
  NULL
  public $request_type =>
  string(12) "notification"
  public $notification_type =>
  string(7) "renewal"
  public $email =>
  NULL
  public $brand =>
  string(6) "drumeo"
  public $valid =>
  int(0)
  public $validation_error =>
  string(31) "All purchased items are expired"
  public $payment_id =>
  NULL
  public $subscription_id =>
  NULL
  public $raw_receipt_response =>
  string(11503) "O:42:"ReceiptValidator\\iTunes\\ProductionResponse":13:{s:14:"\000*\000result_code";i:0;s:12:"\000*\000bundle_id";s:23:"com.drumeo.DrumeoMobile";s:14:"\000*\000app_item_id";N;s:25:"\000*\000original_purchase_date";N;s:15:"\000*\000request_date";N;s:24:"\000*\000receipt_creation_date";N;s:10:"\000*\000receipt";a:24:{s:26:"original_purchase_date_pst";s:39:"2019-12-27 19:50:17 America/Los_Angeles";s:8:"quantity";s:1:"1";s:24:"unique_vendor_identifier";s:36:"91FADB99-4061-42FF-A9A1-4BDD8E9FE931";s:4:"bvrs";s:7:"...
  public $created_at =>
  string(19) "2020-02-06 00:37:03"
  public $updated_at =>
  string(19) "2020-02-06 00:37:03"
}
/app/ecommerce/src/Commands/FixSerializeErrorInAppPurchaseTables.php:127:
class ReceiptValidator\iTunes\ProductionResponse#2236 (13) {
  protected $result_code =>
  int(0)
  protected $bundle_id =>
  string(23) "com.drumeo.DrumeoMobile"
  protected $app_item_id =>
  NULL
  protected $original_purchase_date =>
  NULL
  protected $request_date =>
  NULL
  protected $receipt_creation_date =>
  NULL
  protected $receipt =>
  array(24) {
    'original_purchase_date_pst' =>
    string(39) "2019-12-27 19:50:17 America/Los_Angeles"
    'quantity' =>
    string(1) "1"
    'unique_vendor_identifier' =>
    string(36) "91FADB99-4061-42FF-A9A1-4BDD8E9FE931"
    'bvrs' =>
    string(7) "0.0.176"
    'expires_date_formatted' =>
    string(27) "2020-03-06 00:36:27 Etc/GMT"
    'is_in_intro_offer_period' =>
    string(5) "false"
    'purchase_date_ms' =>
    string(13) "1580949387000"
    'expires_date_formatted_pst' =>
    string(39) "2020-03-05 16:36:27 America/Los_Angeles"
    'is_trial_period' =>
    string(5) "false"
    'item_id' =>
    string(10) "1477997454"
    'unique_identifier' =>
    string(40) "9d82e36e6ebc91ce8d74ed1a45a396d4e1d68678"
    'original_transaction_id' =>
    string(15) "230000628298394"
    'subscription_group_identifier' =>
    string(8) "20531400"
    'app_item_id' =>
    string(10) "1460388277"
    'transaction_id' =>
    string(15) "230000648558030"
    'web_order_line_item_id' =>
    string(15) "230000230485779"
    'version_external_identifier' =>
    string(9) "833201734"
    'purchase_date' =>
    string(27) "2020-02-06 00:36:27 Etc/GMT"
    'product_id' =>
    string(25) "drumeo_app_monthly_member"
    'expires_date' =>
    string(13) "1583454987000"
    'original_purchase_date' =>
    string(27) "2019-12-28 03:50:17 Etc/GMT"
    'purchase_date_pst' =>
    string(39) "2020-02-05 16:36:27 America/Los_Angeles"
    'bid' =>
    string(23) "com.drumeo.DrumeoMobile"
    'original_purchase_date_ms' =>
    string(13) "1577505017000"
  }
  protected $latest_receipt =>
  NULL
  protected $latest_receipt_info =>
  array(0) {
  }
  protected $purchases =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PurchaseItem#2827 (13) {
      protected $quantity =>
      int(1)
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $web_order_line_item_id =>
      string(15) "230000230485779"
      protected $transaction_id =>
      string(15) "230000648558030"
      protected $original_transaction_id =>
      string(15) "230000628298394"
      protected $purchase_date =>
      class Carbon\Carbon#2828 (3) {
        ...
      }
      protected $original_purchase_date =>
      class Carbon\Carbon#2841 (3) {
        ...
      }
      protected $expires_date =>
      class Carbon\Carbon#2845 (3) {
        ...
      }
      protected $cancellation_date =>
      NULL
      protected $is_trial_period =>
      bool(false)
      protected $is_in_intro_offer_period =>
      bool(false)
      protected $promotional_offer_id =>
      NULL
      protected $raw_data =>
      array(24) {
        ...
      }
    }
  }
  protected $pending_renewal_info =>
  array(0) {
  }
  protected $raw_data =>
  array(6) {
    'auto_renew_status' =>
    int(1)
    'status' =>
    int(0)
    'auto_renew_product_id' =>
    string(25) "drumeo_app_monthly_member"
    'receipt' =>
    array(24) {
      'original_purchase_date_pst' =>
      string(39) "2019-12-27 19:50:17 America/Los_Angeles"
      'quantity' =>
      string(1) "1"
      'unique_vendor_identifier' =>
      string(36) "91FADB99-4061-42FF-A9A1-4BDD8E9FE931"
      'bvrs' =>
      string(7) "0.0.176"
      'expires_date_formatted' =>
      string(27) "2020-03-06 00:36:27 Etc/GMT"
      'is_in_intro_offer_period' =>
      string(5) "false"
      'purchase_date_ms' =>
      string(13) "1580949387000"
      'expires_date_formatted_pst' =>
      string(39) "2020-03-05 16:36:27 America/Los_Angeles"
      'is_trial_period' =>
      string(5) "false"
      'item_id' =>
      string(10) "1477997454"
      'unique_identifier' =>
      string(40) "9d82e36e6ebc91ce8d74ed1a45a396d4e1d68678"
      'original_transaction_id' =>
      string(15) "230000628298394"
      'subscription_group_identifier' =>
      string(8) "20531400"
      'app_item_id' =>
      string(10) "1460388277"
      'transaction_id' =>
      string(15) "230000648558030"
      'web_order_line_item_id' =>
      string(15) "230000230485779"
      'version_external_identifier' =>
      string(9) "833201734"
      'purchase_date' =>
      string(27) "2020-02-06 00:36:27 Etc/GMT"
      'product_id' =>
      string(25) "drumeo_app_monthly_member"
      'expires_date' =>
      string(13) "1583454987000"
      'original_purchase_date' =>
      string(27) "2019-12-28 03:50:17 Etc/GMT"
      'purchase_date_pst' =>
      string(39) "2020-02-05 16:36:27 America/Los_Angeles"
      'bid' =>
      string(23) "com.drumeo.DrumeoMobile"
      'original_purchase_date_ms' =>
      string(13) "1577505017000"
    }
    'latest_receipt_info' =>
    array(23) {
      'original_purchase_date_pst' =>
      string(39) "2019-12-27 19:50:17 America/Los_Angeles"
      'quantity' =>
      string(1) "1"
      'unique_vendor_identifier' =>
      string(36) "91FADB99-4061-42FF-A9A1-4BDD8E9FE931"
      'bvrs' =>
      string(7) "0.0.176"
      'expires_date_formatted' =>
      string(27) "2020-03-06 00:36:27 Etc/GMT"
      'is_in_intro_offer_period' =>
      string(5) "false"
      'purchase_date_ms' =>
      string(13) "1580949387000"
      'expires_date_formatted_pst' =>
      string(39) "2020-03-05 16:36:27 America/Los_Angeles"
      'is_trial_period' =>
      string(5) "false"
      'item_id' =>
      string(10) "1477997454"
      'unique_identifier' =>
      string(40) "9d82e36e6ebc91ce8d74ed1a45a396d4e1d68678"
      'original_transaction_id' =>
      string(15) "230000628298394"
      'subscription_group_identifier' =>
      string(8) "20531400"
      'app_item_id' =>
      string(10) "1460388277"
      'transaction_id' =>
      string(15) "230000648558030"
      'web_order_line_item_id' =>
      string(15) "230000230485779"
      'purchase_date' =>
      string(27) "2020-02-06 00:36:27 Etc/GMT"
      'product_id' =>
      string(25) "drumeo_app_monthly_member"
      'expires_date' =>
      string(13) "1583454987000"
      'original_purchase_date' =>
      string(27) "2019-12-28 03:50:17 Etc/GMT"
      'purchase_date_pst' =>
      string(39) "2020-02-05 16:36:27 America/Los_Angeles"
      'bid' =>
      string(23) "com.drumeo.DrumeoMobile"
      'original_purchase_date_ms' =>
      string(13) "1577505017000"
    }
    'latest_receipt' =>
    string(5000) "ewoJInNpZ25hdHVyZSIgPSAiQTFCYjE1SDZ3YUFQZ1FMT2svbU13ZmhTWnFuK3J5NEpXTGREWnpqRWl3Qks1cGdSeE9PV1JjL1dWVCtrSEVjL1RyN1F5cCtPcXlOTm5pTUZpRDRRa1NPdHloQUlHWGk4NDFTclhnOE1rbTBJeXY4b1F2KzIvS0FXR1NxbUxlSVZNWFdIY3dtZS9OOGJuVzh3dFdNdE5uR2RYV2svckRKWXVndTEyNHIyQjJ0M3ZqUHVuMmFFc2laY245dHg2NENvNzBOaHI0WEdsay9UbHp2TUVrSmhtZUFvSEJsUU5tYzNaTWRyM2Y5ODFYbXNvZ25WRFR5VFFEWnRiNjZxcnVtbGREdXYwVDVBK25jN0ZJaXQ5M2JVc3hNaHZWVGFFOE1sMGt6Zi9rd3pVYVVKY0NMMjRJZUtHSHl2UG1naitkelpYeGU4MzYvVFZaRVdWSzBqbXcyc010Z0FBQVdBTUlJRmZEQ0NCR1NnQXdJQkFn"...
  }
  protected $is_retryable =>
  bool(false)
}
---------------------------------------------------------
/app/ecommerce/src/Commands/FixSerializeErrorInAppPurchaseTables.php:126:
class stdClass#2824 (14) {
  public $id =>
  int(568)
  public $receipt =>
  string(6916) "MIIUPQYJKoZIhvcNAQcCoIIULjCCFCoCAQExCzAJBgUrDgMCGgUAMIID3gYJKoZIhvcNAQcBoIIDzwSCA8sxggPHMAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAMswDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGIbbZBTFTMBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEDo/V85kB7iMbb1xVGSuExowHAIBBQIBAQQUx4YO02KysMUL8GwcPA9KPhg+yfwwHgIBCAIBAQQWFhQyMDIwLTAyLTA2VDE3OjIyOjI0WjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
  public $transaction_id =>
  string(15) "170000718474943"
  public $request_type =>
  string(6) "mobile"
  public $notification_type =>
  NULL
  public $email =>
  string(24) "hydrocharlik@hotmail.com"
  public $brand =>
  string(6) "drumeo"
  public $valid =>
  int(1)
  public $validation_error =>
  NULL
  public $payment_id =>
  NULL
  public $subscription_id =>
  int(74051)
  public $raw_receipt_response =>
  string(23587) "O:42:"ReceiptValidator\\iTunes\\ProductionResponse":13:{s:14:"\000*\000result_code";i:0;s:12:"\000*\000bundle_id";s:23:"com.drumeo.DrumeoMobile";s:14:"\000*\000app_item_id";i:1460388277;s:25:"\000*\000original_purchase_date";O:13:"Carbon\\Carbon":3:{s:4:"date";s:26:"2020-02-05 07:10:44.000000";s:13:"timezone_type";i:1;s:8:"timezone";s:6:"+00:00";}s:15:"\000*\000request_date";O:13:"Carbon\\Carbon":3:{s:4:"date";s:26:"2020-02-06 17:22:26.000000";s:13:"timezone_type";i:1;s:8:"timezone";s:6:"+00:00";}s:24:"\000"...
  public $created_at =>
  string(19) "2020-02-06 17:22:25"
  public $updated_at =>
  string(19) "2020-02-06 17:22:26"
}
/app/ecommerce/src/Commands/FixSerializeErrorInAppPurchaseTables.php:127:
class ReceiptValidator\iTunes\ProductionResponse#2236 (13) {
  protected $result_code =>
  int(0)
  protected $bundle_id =>
  string(23) "com.drumeo.DrumeoMobile"
  protected $app_item_id =>
  int(1460388277)
  protected $original_purchase_date =>
  class Carbon\Carbon#2827 (3) {
    public $date =>
    string(26) "2020-02-05 07:10:44.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $request_date =>
  class Carbon\Carbon#2845 (3) {
    public $date =>
    string(26) "2020-02-06 17:22:26.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $receipt_creation_date =>
  class Carbon\Carbon#2841 (3) {
    public $date =>
    string(26) "2020-02-06 17:22:24.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $receipt =>
  array(18) {
    'receipt_type' =>
    string(10) "Production"
    'adam_id' =>
    int(1460388277)
    'app_item_id' =>
    int(1460388277)
    'bundle_id' =>
    string(23) "com.drumeo.DrumeoMobile"
    'application_version' =>
    string(7) "0.0.245"
    'download_id' =>
    int(37069208760659)
    'version_external_identifier' =>
    int(834486669)
    'receipt_creation_date' =>
    string(27) "2020-02-06 17:22:24 Etc/GMT"
    'receipt_creation_date_ms' =>
    string(13) "1581009744000"
    'receipt_creation_date_pst' =>
    string(39) "2020-02-06 09:22:24 America/Los_Angeles"
    'request_date' =>
    string(27) "2020-02-06 17:22:26 Etc/GMT"
    'request_date_ms' =>
    string(13) "1581009746136"
    'request_date_pst' =>
    string(39) "2020-02-06 09:22:26 America/Los_Angeles"
    'original_purchase_date' =>
    string(27) "2020-02-05 07:10:44 Etc/GMT"
    'original_purchase_date_ms' =>
    string(13) "1580886644000"
    'original_purchase_date_pst' =>
    string(39) "2020-02-04 23:10:44 America/Los_Angeles"
    'original_application_version' =>
    string(7) "0.0.245"
    'in_app' =>
    array(1) {
      [0] =>
      array(16) {
        ...
      }
    }
  }
  protected $latest_receipt =>
  string(6916) "MIIUPwYJKoZIhvcNAQcCoIIUMDCCFCwCAQExCzAJBgUrDgMCGgUAMIID4AYJKoZIhvcNAQcBoIID0QSCA80xggPJMAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAMswDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGIbbZBTFTMBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEDo/V85kB7iMbb1xVGSuExowHAIBBQIBAQQUx4YO02KysMUL8GwcPA9KPhg+yfwwHgIBCAIBAQQWFhQyMDIwLTAyLTA2VDE3OjIyOjI0WjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
  protected $latest_receipt_info =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PurchaseItem#2828 (13) {
      protected $quantity =>
      int(1)
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $web_order_line_item_id =>
      string(15) "170000254504003"
      protected $transaction_id =>
      string(15) "170000718474943"
      protected $original_transaction_id =>
      string(15) "170000718474943"
      protected $purchase_date =>
      class Carbon\Carbon#2831 (3) {
        ...
      }
      protected $original_purchase_date =>
      class Carbon\Carbon#2829 (3) {
        ...
      }
      protected $expires_date =>
      class Carbon\Carbon#2840 (3) {
        ...
      }
      protected $cancellation_date =>
      NULL
      protected $is_trial_period =>
      bool(true)
      protected $is_in_intro_offer_period =>
      bool(false)
      protected $promotional_offer_id =>
      NULL
      protected $raw_data =>
      array(17) {
        ...
      }
    }
  }
  protected $purchases =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PurchaseItem#2844 (13) {
      protected $quantity =>
      int(1)
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $web_order_line_item_id =>
      string(15) "170000254504003"
      protected $transaction_id =>
      string(15) "170000718474943"
      protected $original_transaction_id =>
      string(15) "170000718474943"
      protected $purchase_date =>
      class Carbon\Carbon#2832 (3) {
        ...
      }
      protected $original_purchase_date =>
      class Carbon\Carbon#2838 (3) {
        ...
      }
      protected $expires_date =>
      class Carbon\Carbon#2842 (3) {
        ...
      }
      protected $cancellation_date =>
      NULL
      protected $is_trial_period =>
      bool(true)
      protected $is_in_intro_offer_period =>
      bool(false)
      protected $promotional_offer_id =>
      NULL
      protected $raw_data =>
      array(16) {
        ...
      }
    }
  }
  protected $pending_renewal_info =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PendingRenewalInfo#2848 (7) {
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $auto_renew_product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $original_transaction_id =>
      string(15) "170000718474943"
      protected $auto_renew_status =>
      bool(true)
      protected $expiration_intent =>
      NULL
      protected $is_in_billing_retry_period =>
      NULL
      protected $raw_data =>
      array(4) {
        ...
      }
    }
  }
  protected $raw_data =>
  array(6) {
    'status' =>
    int(0)
    'environment' =>
    string(10) "Production"
    'receipt' =>
    array(18) {
      'receipt_type' =>
      string(10) "Production"
      'adam_id' =>
      int(1460388277)
      'app_item_id' =>
      int(1460388277)
      'bundle_id' =>
      string(23) "com.drumeo.DrumeoMobile"
      'application_version' =>
      string(7) "0.0.245"
      'download_id' =>
      int(37069208760659)
      'version_external_identifier' =>
      int(834486669)
      'receipt_creation_date' =>
      string(27) "2020-02-06 17:22:24 Etc/GMT"
      'receipt_creation_date_ms' =>
      string(13) "1581009744000"
      'receipt_creation_date_pst' =>
      string(39) "2020-02-06 09:22:24 America/Los_Angeles"
      'request_date' =>
      string(27) "2020-02-06 17:22:26 Etc/GMT"
      'request_date_ms' =>
      string(13) "1581009746136"
      'request_date_pst' =>
      string(39) "2020-02-06 09:22:26 America/Los_Angeles"
      'original_purchase_date' =>
      string(27) "2020-02-05 07:10:44 Etc/GMT"
      'original_purchase_date_ms' =>
      string(13) "1580886644000"
      'original_purchase_date_pst' =>
      string(39) "2020-02-04 23:10:44 America/Los_Angeles"
      'original_application_version' =>
      string(7) "0.0.245"
      'in_app' =>
      array(1) {
        ...
      }
    }
    'latest_receipt_info' =>
    array(1) {
      [0] =>
      array(17) {
        ...
      }
    }
    'latest_receipt' =>
    string(6916) "MIIUPwYJKoZIhvcNAQcCoIIUMDCCFCwCAQExCzAJBgUrDgMCGgUAMIID4AYJKoZIhvcNAQcBoIID0QSCA80xggPJMAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAMswDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGIbbZBTFTMBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEDo/V85kB7iMbb1xVGSuExowHAIBBQIBAQQUx4YO02KysMUL8GwcPA9KPhg+yfwwHgIBCAIBAQQWFhQyMDIwLTAyLTA2VDE3OjIyOjI0WjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
    'pending_renewal_info' =>
    array(1) {
      [0] =>
      array(4) {
        ...
      }
    }
  }
  protected $is_retryable =>
  bool(false)
}
---------------------------------------------------------
/app/ecommerce/src/Commands/FixSerializeErrorInAppPurchaseTables.php:126:
class stdClass#2825 (14) {
  public $id =>
  int(569)
  public $receipt =>
  string(6888) "MIIUKQYJKoZIhvcNAQcCoIIUGjCCFBYCAQExCzAJBgUrDgMCGgUAMIIDygYJKoZIhvcNAQcBoIIDuwSCA7cxggOzMAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAMIwDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGP7aAr6jBMBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEOwwdw16JZyHASbHjrt0ffEwHAIBBQIBAQQU3/3P4VNM8XRKAGDPcLij7Te5QDkwHgIBCAIBAQQWFhQyMDIwLTAyLTA2VDE5OjM4OjAwWjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
  public $transaction_id =>
  string(15) "300000545742808"
  public $request_type =>
  string(6) "mobile"
  public $notification_type =>
  NULL
  public $email =>
  string(26) "chrissaunders12@icloud.com"
  public $brand =>
  string(6) "drumeo"
  public $valid =>
  int(1)
  public $validation_error =>
  NULL
  public $payment_id =>
  NULL
  public $subscription_id =>
  int(74053)
  public $raw_receipt_response =>
  string(23491) "O:42:"ReceiptValidator\\iTunes\\ProductionResponse":13:{s:14:"\000*\000result_code";i:0;s:12:"\000*\000bundle_id";s:23:"com.drumeo.DrumeoMobile";s:14:"\000*\000app_item_id";i:1460388277;s:25:"\000*\000original_purchase_date";O:13:"Carbon\\Carbon":3:{s:4:"date";s:26:"2020-02-05 18:16:48.000000";s:13:"timezone_type";i:1;s:8:"timezone";s:6:"+00:00";}s:15:"\000*\000request_date";O:13:"Carbon\\Carbon":3:{s:4:"date";s:26:"2020-02-06 19:38:02.000000";s:13:"timezone_type";i:1;s:8:"timezone";s:6:"+00:00";}s:24:"\000"...
  public $created_at =>
  string(19) "2020-02-06 19:38:01"
  public $updated_at =>
  string(19) "2020-02-06 19:38:03"
}
/app/ecommerce/src/Commands/FixSerializeErrorInAppPurchaseTables.php:127:
class ReceiptValidator\iTunes\ProductionResponse#2236 (13) {
  protected $result_code =>
  int(0)
  protected $bundle_id =>
  string(23) "com.drumeo.DrumeoMobile"
  protected $app_item_id =>
  int(1460388277)
  protected $original_purchase_date =>
  class Carbon\Carbon#2848 (3) {
    public $date =>
    string(26) "2020-02-05 18:16:48.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $request_date =>
  class Carbon\Carbon#2844 (3) {
    public $date =>
    string(26) "2020-02-06 19:38:02.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $receipt_creation_date =>
  class Carbon\Carbon#2842 (3) {
    public $date =>
    string(26) "2020-02-06 19:38:00.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $receipt =>
  array(18) {
    'receipt_type' =>
    string(10) "Production"
    'adam_id' =>
    int(1460388277)
    'app_item_id' =>
    int(1460388277)
    'bundle_id' =>
    string(23) "com.drumeo.DrumeoMobile"
    'application_version' =>
    string(7) "0.0.245"
    'download_id' =>
    int(70053075593409)
    'version_external_identifier' =>
    int(834486669)
    'receipt_creation_date' =>
    string(27) "2020-02-06 19:38:00 Etc/GMT"
    'receipt_creation_date_ms' =>
    string(13) "1581017880000"
    'receipt_creation_date_pst' =>
    string(39) "2020-02-06 11:38:00 America/Los_Angeles"
    'request_date' =>
    string(27) "2020-02-06 19:38:02 Etc/GMT"
    'request_date_ms' =>
    string(13) "1581017882028"
    'request_date_pst' =>
    string(39) "2020-02-06 11:38:02 America/Los_Angeles"
    'original_purchase_date' =>
    string(27) "2020-02-05 18:16:48 Etc/GMT"
    'original_purchase_date_ms' =>
    string(13) "1580926608000"
    'original_purchase_date_pst' =>
    string(39) "2020-02-05 10:16:48 America/Los_Angeles"
    'original_application_version' =>
    string(7) "0.0.245"
    'in_app' =>
    array(1) {
      [0] =>
      array(16) {
        ...
      }
    }
  }
  protected $latest_receipt =>
  string(6868) "MIIUGwYJKoZIhvcNAQcCoIIUDDCCFAgCAQExCzAJBgUrDgMCGgUAMIIDvAYJKoZIhvcNAQcBoIIDrQSCA6kxggOlMAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAMIwDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGP7aAr6jBMBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEOwwdw16JZyHASbHjrt0ffEwHAIBBQIBAQQU3/3P4VNM8XRKAGDPcLij7Te5QDkwHgIBCAIBAQQWFhQyMDIwLTAyLTA2VDE5OjM4OjAwWjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
  protected $latest_receipt_info =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PurchaseItem#2838 (13) {
      protected $quantity =>
      int(1)
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $web_order_line_item_id =>
      string(15) "300000198669643"
      protected $transaction_id =>
      string(15) "300000545742808"
      protected $original_transaction_id =>
      string(15) "300000545742808"
      protected $purchase_date =>
      class Carbon\Carbon#2832 (3) {
        ...
      }
      protected $original_purchase_date =>
      class Carbon\Carbon#2828 (3) {
        ...
      }
      protected $expires_date =>
      class Carbon\Carbon#2840 (3) {
        ...
      }
      protected $cancellation_date =>
      NULL
      protected $is_trial_period =>
      bool(true)
      protected $is_in_intro_offer_period =>
      bool(false)
      protected $promotional_offer_id =>
      NULL
      protected $raw_data =>
      array(17) {
        ...
      }
    }
  }
  protected $purchases =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PurchaseItem#2829 (13) {
      protected $quantity =>
      int(1)
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $web_order_line_item_id =>
      string(15) "300000198669643"
      protected $transaction_id =>
      string(15) "300000545742808"
      protected $original_transaction_id =>
      string(15) "300000545742808"
      protected $purchase_date =>
      class Carbon\Carbon#2831 (3) {
        ...
      }
      protected $original_purchase_date =>
      class Carbon\Carbon#2841 (3) {
        ...
      }
      protected $expires_date =>
      class Carbon\Carbon#2845 (3) {
        ...
      }
      protected $cancellation_date =>
      NULL
      protected $is_trial_period =>
      bool(true)
      protected $is_in_intro_offer_period =>
      bool(false)
      protected $promotional_offer_id =>
      NULL
      protected $raw_data =>
      array(16) {
        ...
      }
    }
  }
  protected $pending_renewal_info =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PendingRenewalInfo#2827 (7) {
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $auto_renew_product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $original_transaction_id =>
      string(15) "300000545742808"
      protected $auto_renew_status =>
      bool(true)
      protected $expiration_intent =>
      NULL
      protected $is_in_billing_retry_period =>
      NULL
      protected $raw_data =>
      array(4) {
        ...
      }
    }
  }
  protected $raw_data =>
  array(6) {
    'status' =>
    int(0)
    'environment' =>
    string(10) "Production"
    'receipt' =>
    array(18) {
      'receipt_type' =>
      string(10) "Production"
      'adam_id' =>
      int(1460388277)
      'app_item_id' =>
      int(1460388277)
      'bundle_id' =>
      string(23) "com.drumeo.DrumeoMobile"
      'application_version' =>
      string(7) "0.0.245"
      'download_id' =>
      int(70053075593409)
      'version_external_identifier' =>
      int(834486669)
      'receipt_creation_date' =>
      string(27) "2020-02-06 19:38:00 Etc/GMT"
      'receipt_creation_date_ms' =>
      string(13) "1581017880000"
      'receipt_creation_date_pst' =>
      string(39) "2020-02-06 11:38:00 America/Los_Angeles"
      'request_date' =>
      string(27) "2020-02-06 19:38:02 Etc/GMT"
      'request_date_ms' =>
      string(13) "1581017882028"
      'request_date_pst' =>
      string(39) "2020-02-06 11:38:02 America/Los_Angeles"
      'original_purchase_date' =>
      string(27) "2020-02-05 18:16:48 Etc/GMT"
      'original_purchase_date_ms' =>
      string(13) "1580926608000"
      'original_purchase_date_pst' =>
      string(39) "2020-02-05 10:16:48 America/Los_Angeles"
      'original_application_version' =>
      string(7) "0.0.245"
      'in_app' =>
      array(1) {
        ...
      }
    }
    'latest_receipt_info' =>
    array(1) {
      [0] =>
      array(17) {
        ...
      }
    }
    'latest_receipt' =>
    string(6868) "MIIUGwYJKoZIhvcNAQcCoIIUDDCCFAgCAQExCzAJBgUrDgMCGgUAMIIDvAYJKoZIhvcNAQcBoIIDrQSCA6kxggOlMAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAMIwDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGP7aAr6jBMBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEOwwdw16JZyHASbHjrt0ffEwHAIBBQIBAQQU3/3P4VNM8XRKAGDPcLij7Te5QDkwHgIBCAIBAQQWFhQyMDIwLTAyLTA2VDE5OjM4OjAwWjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
    'pending_renewal_info' =>
    array(1) {
      [0] =>
      array(4) {
        ...
      }
    }
  }
  protected $is_retryable =>
  bool(false)
}
---------------------------------------------------------
/app/ecommerce/src/Commands/FixSerializeErrorInAppPurchaseTables.php:126:
class stdClass#2826 (14) {
  public $id =>
  int(570)
  public $receipt =>
  string(6864) "MIIUGAYJKoZIhvcNAQcCoIIUCTCCFAUCAQExCzAJBgUrDgMCGgUAMIIDuQYJKoZIhvcNAQcBoIIDqgSCA6YxggOiMAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAMIwDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGGYZWqLnwMBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEH6XXv7R53npp37/aaV6vlEwHAIBBQIBAQQUKReGrJaRS/zfJwkB5/TAK+oPUDswHgIBCAIBAQQWFhQyMDIwLTAyLTA2VDE5OjU5OjAyWjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
  public $transaction_id =>
  string(14) "80000682453487"
  public $request_type =>
  string(6) "mobile"
  public $notification_type =>
  NULL
  public $email =>
  string(16) "nsjkeee@gmai.com"
  public $brand =>
  string(6) "drumeo"
  public $valid =>
  int(1)
  public $validation_error =>
  NULL
  public $payment_id =>
  NULL
  public $subscription_id =>
  int(74054)
  public $raw_receipt_response =>
  string(23491) "O:42:"ReceiptValidator\\iTunes\\ProductionResponse":13:{s:14:"\000*\000result_code";i:0;s:12:"\000*\000bundle_id";s:23:"com.drumeo.DrumeoMobile";s:14:"\000*\000app_item_id";i:1460388277;s:25:"\000*\000original_purchase_date";O:13:"Carbon\\Carbon":3:{s:4:"date";s:26:"2020-02-06 19:55:35.000000";s:13:"timezone_type";i:1;s:8:"timezone";s:6:"+00:00";}s:15:"\000*\000request_date";O:13:"Carbon\\Carbon":3:{s:4:"date";s:26:"2020-02-06 19:59:04.000000";s:13:"timezone_type";i:1;s:8:"timezone";s:6:"+00:00";}s:24:"\000"...
  public $created_at =>
  string(19) "2020-02-06 19:59:03"
  public $updated_at =>
  string(19) "2020-02-06 19:59:05"
}
/app/ecommerce/src/Commands/FixSerializeErrorInAppPurchaseTables.php:127:
class ReceiptValidator\iTunes\ProductionResponse#2236 (13) {
  protected $result_code =>
  int(0)
  protected $bundle_id =>
  string(23) "com.drumeo.DrumeoMobile"
  protected $app_item_id =>
  int(1460388277)
  protected $original_purchase_date =>
  class Carbon\Carbon#2827 (3) {
    public $date =>
    string(26) "2020-02-06 19:55:35.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $request_date =>
  class Carbon\Carbon#2829 (3) {
    public $date =>
    string(26) "2020-02-06 19:59:04.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $receipt_creation_date =>
  class Carbon\Carbon#2845 (3) {
    public $date =>
    string(26) "2020-02-06 19:59:02.000000"
    public $timezone_type =>
    int(1)
    public $timezone =>
    string(6) "+00:00"
  }
  protected $receipt =>
  array(18) {
    'receipt_type' =>
    string(10) "Production"
    'adam_id' =>
    int(1460388277)
    'app_item_id' =>
    int(1460388277)
    'bundle_id' =>
    string(23) "com.drumeo.DrumeoMobile"
    'application_version' =>
    string(7) "0.0.245"
    'download_id' =>
    int(28064770210288)
    'version_external_identifier' =>
    int(834486669)
    'receipt_creation_date' =>
    string(27) "2020-02-06 19:59:02 Etc/GMT"
    'receipt_creation_date_ms' =>
    string(13) "1581019142000"
    'receipt_creation_date_pst' =>
    string(39) "2020-02-06 11:59:02 America/Los_Angeles"
    'request_date' =>
    string(27) "2020-02-06 19:59:04 Etc/GMT"
    'request_date_ms' =>
    string(13) "1581019144105"
    'request_date_pst' =>
    string(39) "2020-02-06 11:59:04 America/Los_Angeles"
    'original_purchase_date' =>
    string(27) "2020-02-06 19:55:35 Etc/GMT"
    'original_purchase_date_ms' =>
    string(13) "1581018935000"
    'original_purchase_date_pst' =>
    string(39) "2020-02-06 11:55:35 America/Los_Angeles"
    'original_application_version' =>
    string(7) "0.0.245"
    'in_app' =>
    array(1) {
      [0] =>
      array(16) {
        ...
      }
    }
  }
  protected $latest_receipt =>
  string(6880) "MIIUJAYJKoZIhvcNAQcCoIIUFTCCFBECAQExCzAJBgUrDgMCGgUAMIIDxQYJKoZIhvcNAQcBoIIDtgSCA7IxggOuMAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAMIwDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGGYZWqLnwMBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEH6XXv7R53npp37/aaV6vlEwHAIBBQIBAQQUKReGrJaRS/zfJwkB5/TAK+oPUDswHgIBCAIBAQQWFhQyMDIwLTAyLTA2VDE5OjU5OjAyWjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
  protected $latest_receipt_info =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PurchaseItem#2841 (13) {
      protected $quantity =>
      int(1)
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $web_order_line_item_id =>
      string(14) "80000232446737"
      protected $transaction_id =>
      string(14) "80000682453487"
      protected $original_transaction_id =>
      string(14) "80000682453487"
      protected $purchase_date =>
      class Carbon\Carbon#2831 (3) {
        ...
      }
      protected $original_purchase_date =>
      class Carbon\Carbon#2838 (3) {
        ...
      }
      protected $expires_date =>
      class Carbon\Carbon#2840 (3) {
        ...
      }
      protected $cancellation_date =>
      NULL
      protected $is_trial_period =>
      bool(true)
      protected $is_in_intro_offer_period =>
      bool(false)
      protected $promotional_offer_id =>
      NULL
      protected $raw_data =>
      array(17) {
        ...
      }
    }
  }
  protected $purchases =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PurchaseItem#2828 (13) {
      protected $quantity =>
      int(1)
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $web_order_line_item_id =>
      string(14) "80000232446737"
      protected $transaction_id =>
      string(14) "80000682453487"
      protected $original_transaction_id =>
      string(14) "80000682453487"
      protected $purchase_date =>
      class Carbon\Carbon#2832 (3) {
        ...
      }
      protected $original_purchase_date =>
      class Carbon\Carbon#2842 (3) {
        ...
      }
      protected $expires_date =>
      class Carbon\Carbon#2844 (3) {
        ...
      }
      protected $cancellation_date =>
      NULL
      protected $is_trial_period =>
      bool(true)
      protected $is_in_intro_offer_period =>
      bool(false)
      protected $promotional_offer_id =>
      NULL
      protected $raw_data =>
      array(16) {
        ...
      }
    }
  }
  protected $pending_renewal_info =>
  array(1) {
    [0] =>
    class ReceiptValidator\iTunes\PendingRenewalInfo#2848 (7) {
      protected $product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $auto_renew_product_id =>
      string(25) "drumeo_app_monthly_member"
      protected $original_transaction_id =>
      string(14) "80000682453487"
      protected $auto_renew_status =>
      bool(true)
      protected $expiration_intent =>
      NULL
      protected $is_in_billing_retry_period =>
      NULL
      protected $raw_data =>
      array(4) {
        ...
      }
    }
  }
  protected $raw_data =>
  array(6) {
    'status' =>
    int(0)
    'environment' =>
    string(10) "Production"
    'receipt' =>
    array(18) {
      'receipt_type' =>
      string(10) "Production"
      'adam_id' =>
      int(1460388277)
      'app_item_id' =>
      int(1460388277)
      'bundle_id' =>
      string(23) "com.drumeo.DrumeoMobile"
      'application_version' =>
      string(7) "0.0.245"
      'download_id' =>
      int(28064770210288)
      'version_external_identifier' =>
      int(834486669)
      'receipt_creation_date' =>
      string(27) "2020-02-06 19:59:02 Etc/GMT"
      'receipt_creation_date_ms' =>
      string(13) "1581019142000"
      'receipt_creation_date_pst' =>
      string(39) "2020-02-06 11:59:02 America/Los_Angeles"
      'request_date' =>
      string(27) "2020-02-06 19:59:04 Etc/GMT"
      'request_date_ms' =>
      string(13) "1581019144105"
      'request_date_pst' =>
      string(39) "2020-02-06 11:59:04 America/Los_Angeles"
      'original_purchase_date' =>
      string(27) "2020-02-06 19:55:35 Etc/GMT"
      'original_purchase_date_ms' =>
      string(13) "1581018935000"
      'original_purchase_date_pst' =>
      string(39) "2020-02-06 11:55:35 America/Los_Angeles"
      'original_application_version' =>
      string(7) "0.0.245"
      'in_app' =>
      array(1) {
        ...
      }
    }
    'latest_receipt_info' =>
    array(1) {
      [0] =>
      array(17) {
        ...
      }
    }
    'latest_receipt' =>
    string(6880) "MIIUJAYJKoZIhvcNAQcCoIIUFTCCFBECAQExCzAJBgUrDgMCGgUAMIIDxQYJKoZIhvcNAQcBoIIDtgSCA7IxggOuMAoCARQCAQEEAgwAMAsCARkCAQEEAwIBAzAMAgEKAgEBBAQWAjkrMAwCAQ4CAQEEBAICAMIwDQIBDQIBAQQFAgMB/PwwDgIBAQIBAQQGAgRXC8G1MA4CAQkCAQEEBgIEUDI1MzAOAgELAgEBBAYCBAcKSUIwDgIBEAIBAQQGAgQxvUGNMBACAQ8CAQEECAIGGYZWqLnwMBECAQMCAQEECQwHMC4wLjI0NTARAgETAgEBBAkMBzAuMC4yNDUwFAIBAAIBAQQMDApQcm9kdWN0aW9uMBgCAQQCAQIEEH6XXv7R53npp37/aaV6vlEwHAIBBQIBAQQUKReGrJaRS/zfJwkB5/TAK+oPUDswHgIBCAIBAQQWFhQyMDIwLTAyLTA2VDE5OjU5OjAyWjAeAgEMAgEBBBYWFDIwMjAtMDIt"...
    'pending_renewal_info' =>
    array(1) {
      [0] =>
      array(4) {
        ...
      }
    }
  }
  protected $is_retryable =>
  bool(false)
}
---------------------------------------------------------
```