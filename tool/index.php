<?php

require __DIR__ . '/_config.php';

// Route process
$route = isset($_GET['route']) ? $_GET['route'] : null;
switch ($route) {
  case 'clear':
    session_destroy();
    // Redirect back
    header('Location: ./index.php');
    break;

  case 'order':
  case 'index':
  default:
    # code...
    break;
}

// Get the order from session
$order = isset($_SESSION['linePayOrder']) ? $_SESSION['linePayOrder'] : [];
// Get last form data if exists
$config = isset($_SESSION['config']) ? $_SESSION['config'] : [];
// Get merchant list if exist
$merchants = Merchant::getList();
// Get log
$logs = isset($_SESSION['logs']) ? $_SESSION['logs'] : [];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="icon" type="image/x-icon" class="js-site-favicon" href="https://github.com/fluidicon.png">
    <title>Tool - yidas/line-pay-sdk-php</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <style>
      pre.log {
        word-break: break-all; 
        white-space: pre-wrap; 
        font-size: 9pt;
        background-color: #f5f5f5;
        padding: 5px;
      }
    </style>
    <script>
      /**
       * Form Submit for Online or Offline API ways
       * 
       * @param element form
       */
      function formSubmit(form) {
        form.action = "request.php";
        if (form.otk.value) {
          form.action = "onetimekeys-pay.php";
        }
        else if (form.useRegKey.checked) {
          switch (form.preapprovedAction.value) {
            case 'pay':
              form.action = "preapproved.php";
              break;
            case 'checkAuth':
              form.action = "preapproved-check.php?creditCardAuth=true";
              break;
            case 'check':
            default:
              form.action = "preapproved-check.php";
              break;
          }
        }
        else if (form.transactionId.value) {
          form.action = "details.php";
        }
        else if (form.transactionIdForUserInfo.value) {
          form.action = "get-user-info.php";
        }
        form.submit();
        return;
      }
    </script>
</head>
<body>
<div style="padding:30px 10px; max-width: 600px; margin: auto;">
  <h3>LINE Pay API Tool <a href="https://github.com/yidas/line-pay-sdk-php"><img src="https://github.com/favicon.ico" height="20" width="20"></a></h3>

  <?php if($route=='order'): ?>
  <?php $status = (!isset($order['isSuccessful'])) ? 'none' : (($order['isSuccessful']) ? 'successful' : 'failed') ?>

  <div class="alert alert-<?php if($status=='none'):?>warning<?php elseif($status=='successful'):?>success<?php else:?>danger<?php endif?>" role="alert">
  <h4 class="alert-heading"><?php if($status=='none'):?>Transaction not found<?php elseif($status=='successful'):?>Transaction complete<?php else:?>Transaction failed<?php endif?>!</h4>
    <?php if($status!='none'):?>
    <?php if($status=='failed'):?>
    <hr>
    <p>ErrorCode: <?=$order['confirmCode']?></p>
    <p>ErrorMessage: <?=$order['confirmMessage']?></p>
    <?php endif ?>
    <hr>
    <p>TransactionId: <?=$order['transactionId']?></p>
    <p>OrderId: <?=$order['params']['orderId']?></p>
    <p>ProductName: <?= isset($order['params']['productName']) ? $order['params']['productName'] : $order['params']['packages'][0]['products'][0]['name']?></p>
    <p>Amount: <?=$order['params']['amount']?></p>
    <p>Currency: <?=$order['params']['currency']?></p>
    <hr>
    <p>Environment: <?php if($order['isSandbox']):?>Sandbox<?php else:?>Real<?php endif ?></p>
    <?php endif ?>
    <?php if(isset($order['info']['refundList'])):?>
      <hr>
      <p><strong>Refund Info</strong></p>
      <?php foreach ($order['info']['refundList'] as $key => $refund): ?>
      <p>
        Refund (<?=date('c', strtotime($refund['refundTransactionDate']))?>)<br>
        TransactionId: <?=$refund['refundTransactionId']?><br>
        Amount: <?=$refund['refundAmount']?>
      </p>
      <?php endforeach ?>
    <?php endif ?>
    <?php if(isset($config['captureFalse'])):?>
      <hr>
      <p><strong>Capture Info</strong></p>
      <p>Pay Status: <?=isset($order['info']['payStatus']) ? $order['info']['payStatus'] : ''?></p>
      <div class="clearfix">
        <div class="float-left">
          <div class="input-group">
            <input type="text" id="capture-amount" class="form-control" placeholder="Amount" size="7">
            <div class="input-group-append">
              <button class="btn btn-primary" type="button" onclick="location.href='./capture.php?transactionId=<?=$order['transactionId']?>&amount=' + document.getElementById('capture-amount').value">Capture</button>
            </div>
          </div>
        </div>
        <div class="float-right">
          <a href="./void.php?transactionId=<?=$order['transactionId']?>" class="btn btn-danger">Void</a>
        </div>
      </div>
    <?php endif ?>
    <?php if(isset($config['preapproved'])):?>
      <hr>
      <p><strong>Preapproved Info</strong></p>
      <p>regKey: <?=$config['regKey']?></p>
    <?php endif ?>
    <hr>
    <div class="clearfix">
      <div class="float-left">
        <a href="./index.php" class="btn btn-light">Go Back</a>
      </div>
      <div class="float-right">
        <?php if($status=='successful'):?>

        <div class="input-group">
          <input type="text" id="refund-amount" class="form-control" placeholder="Amount" size="7">
          <div class="input-group-append">
            <button class="btn btn-danger" type="button" onclick="location.href='./refund.php?transactionId=<?=$order['transactionId']?>&amount=' + document.getElementById('refund-amount').value">Refund</button>
          </div>
        </div>
        <!-- <input type="text" class="form-control" size="5" style="display: inline; width: 50px;" />
        <a href="./refund.php?transactionId=<?=$order['transactionId']?>" class="btn btn-danger">Refund</a> -->
        <?php endif ?>
      </div>
    </div>
  </div>

  <?php else: ?>

  <form method="POST" onsubmit="formSubmit(this);return;">
    <?php if($merchants): ?>
    <div class="merchant-block form-group" data-block-id="config" style="display: none;">
      <label for="inputChannelId">Merchant (<a class="btn-merchant-switch" href="javascript:void(0);" data-block-id="config">Switch to Custom</a>)</label>
      <select class="form-control" name="merchant" disabled>
      <?php foreach($merchants as $key => $merchant): ?>
        <option value="<?=$key?>" <?php if(isset($config['merchant']) && $config['merchant']==$key):?>selected<?php endif ?>><?=isset($merchant['title']) ? $merchant['title'] : "(Merchant - {$key})"?></option>
      <?php endforeach ?>
      </select>
     </div>
    <?php endif ?>
    <div class="merchant-block" data-block-id="custom">
      <div class="form-group">
        <label for="inputChannelId">ChannelId <?php if($merchants): ?>(<a class="btn-merchant-switch" href="javascript:void(0);" data-block-id="custom">Switch to Config</a>)<?php endif ?></label>
        <input type="text" class="form-control" id="inputChannelId" name="channelId" placeholder="Enter X-LINE-ChannelId" value="<?=(!isset($config['merchant']) && isset($config['channelId'])) ? $config['channelId'] : ''?>" required>
      </div>
      <div class="form-group">
        <label for="inputChannelSecret">ChannelSecret</label>
        <input type="text" class="form-control" id="inputChannelSecret" name="channelSecret" placeholder="Enter X-LINE-ChannelSecret" value="<?=(!isset($config['merchant']) && isset($config['channelSecret'])) ? $config['channelSecret'] : ''?>" required>
      </div>
    </div>
    <div class="form-group">
      <label for="inputProductName">ProductName</label>
      <input type="text" class="form-control" id="inputProductName" name="productName" placeholder="Your product name"  value="<?=isset($config['productName']) ? $config['productName'] : 'QA Service Pack'?>">
    </div>
    <div class="form-group">
      <label for="inputAmount">Amount</label>
      <input type="text" class="form-control" id="inputAmount" name="amount" placeholder="Your product amount" value="<?=isset($config['amount']) ? $config['amount'] : '250'?>" required value="250">
    </div>
    <div class="form-group">
      <label for="inputCurrency">Currency</label>
      <input type="text" class="form-control" id="inputCurrency" name="currency" placeholder="Currency" value="<?=isset($config['currency']) ? $config['currency'] : 'TWD'?>" required>
    </div>
    <div class="form-group">
      <label for="inputCurrency">Offline API option - OneTimeKey (<a href="https://sandbox-web-pay.line.me/web/sandbox/payment/otk" target="_blank">Global</a> / <a href="https://sandbox-web-pay.line.me/web/sandbox/payment/oneTimeKey?countryCode=TW&paymentMethod=card&preset=1" target="_blank">TW</a> Simulation)</label>
      <input type="text" class="form-control" id="inputOtk" name="otk" placeholder="LINE Pay My Code (Fill in to switch to Offline API)" value="">
    </div>
    <div class="row">
      <div class="col col-4">
        <div class="form-check">
          <input type="checkbox" class="form-check-input" id="inputSandbox" name="isSandbox" <?=isset($config['isSandbox']) && !$config['isSandbox'] ? '' : 'checked'?>>
          <label class="form-check-label" for="inputSandbox">Sandbox</label>
        </div>
      </div>
      <div class="col col-8 text-right">
        <a href="javascript:void(0);" data-toggle="collapse" data-target="#collapseSignatureTool">Signature Tool</a>
        |
        <a href="javascript:void(0);" data-toggle="collapse" data-target="#collapseMoreSettings">More Settings</a>
        |
        <a href="javascript:void(0);" data-toggle="modal" data-target="#logModal">View Logs</a>
      </div>
    </div>
    <div class="collapse" id="collapseSignatureTool">
      <div class="card card-body">
        <div class="form-group">
          <label>X-LINE-Authorization Signature Generator</label>
          <div class="input-group input-group-sm">
            <div class="input-group-prepend">
              <span class="input-group-text" style="min-width: 150px;">Channel Secret</span>
            </div>
            <input type="text" name="signature-secret" class="form-control" placeholder="Merchant's Channel Secret">
          </div>
          <div class="input-group input-group-sm">
            <div class="input-group-prepend">
              <span class="input-group-text" style="min-width: 150px;">URL Path</span>
            </div>
            <input type="text" name="signature-path" class="form-control" value="/v3/payments/request" placeholder="URL Path from the API">
          </div>
          <div class="input-group input-group-sm">
            <div class="input-group-prepend">
              <span class="input-group-text" style="min-width: 150px;">Nonce</span>
            </div>
            <input type="text" name="signature-nonce" class="form-control" placeholder="Nonce for each API request">
          </div>
          <div class="input-group input-group-sm">
            <div class="input-group-prepend">
              <span class="input-group-text" style="min-width: 150px;">Body / Query String</span>
            </div>
            <input type="text" name="signature-body" class="form-control" placeholder="RequestBody for POST method / Query String for GET method">
          </div>
          <div class="input-group">
            <input type="text" class="form-control" name="signature-value" placeholder="X-LINE-Authorization Signature will be generated here">
            <div class="input-group-append">
              <button class="btn btn-outline-secondary btn-sinature-generate" type="button">Generate</button>
            </div>
          </div>
          <hr>
          <blockquote class="blockquote md-0" style="font-size: 12px;">
          <p><b>HTTP Method : GET</b></p>
          <p>Signature = Base64(HMAC-SHA256(Your ChannelSecret, (Your ChannelSecret + URL Path + Query String + nonce)))<br>Query String : A query string except <code>?</code> (Example: Name1=Value1&Name2=Value2...)</p>
          <p><b>HTTP Method : POST</b></p>
          <p>Signature = Base64(HMAC-SHA256(Your ChannelSecret, (Your ChannelSecret + URL Path + RequestBody + nonce)))</p>
          </blockquote>
        </div>
      </div>
    </div>
    <div class="collapse" id="collapseMoreSettings">
      <div class="card card-body">
        <div class="form-group">
          <label>PreApproved <font color="#cccccc"><i>(Online Only)</i></font></label>
          <div class="form-check">
            <input type="checkbox" class="form-check-input" id="inputPreapproved" name="preapproved" <?=isset($config['preapproved']) ? 'checked' : ''?>>
            <label class="form-check-label" for="inputPreapproved">PayType: <code>PREAPPROVED</code></label>
          </div>
          <div class="form-check">
            <input type="checkbox" class="form-check-input" id="inputUseRegKey" name="useRegKey">
            <label class="form-check-label" for="inputUseRegKey">Preapproved by <code>regKey</code></label>
            <input type="text" class="form-control form-control-sm" id="inputRegKey" name="regKey" placeholder="Preapproved regKey" value="<?=isset($config['regKey']) ? $config['regKey'] : ''?>">
            <div class="form-check">
              <input type="radio" class="form-check-input" id="inputPreapprovedCheck" name="preapprovedAction" value="check">
              <label class="form-check-label" for="inputPreapprovedCheck">Check <code>regKey</code> <font color="#cccccc"><i>Default</i></font></label>
            </div>
            <div class="form-check">
            <input type="radio" class="form-check-input" id="inputPreapprovedCheckAuth" name="preapprovedAction" value="checkAuth">
              <label class="form-check-label" for="inputPreapprovedCheckAuth">Check <code>regKey</code> with <code>creditCardAuth</code></label>
            </div>
            <div class="form-check">
              <input type="radio" class="form-check-input" id="inputPreapprovedPay" name="preapprovedAction" value="pay">
              <label class="form-check-label" for="inputPreapprovedPay">Pay preapproved by <code>regKey</code></label>
            </div>
          </div>
        </div>
        <hr>
        <div class="form-check">
          <input type="checkbox" class="form-check-input" id="inputCaptureFalse" name="captureFalse" <?=isset($config['captureFalse']) ? 'checked' : ''?>>
          <label class="form-check-label" for="inputCaptureFalse">Capture: <code>false</code></label>
        </div>
        <hr>
        <div class="form-group">
          <label>Overwrite Fields</label>
          <div class="input-group input-group-sm">
            <div class="input-group-prepend">
              <span class="input-group-text" style="min-width: 135px;">OrderId</span>
            </div>
            <input type="text" name="orderId" class="form-control" placeholder="Fill in to overwrite orderId">
          </div>
          <div class="input-group input-group-sm">
            <div class="input-group-prepend">
              <span class="input-group-text" style="min-width: 135px;">ImageUrl</span>
            </div>
            <input type="text" name="imageUrl" class="form-control" placeholder="Fill in to overwrite imageUrl (Online Only)">
          </div>
          <div class="input-group input-group-sm">
            <div class="input-group-prepend">
              <span class="input-group-text" style="min-width: 135px;">ConfirmUrl</span>
            </div>
            <input type="text" name="confirmUrl" class="form-control" placeholder="Fill in to overwrite confirmUrl (Online Only)">
          </div>
          <div class="input-group input-group-sm">
            <div class="input-group-prepend">
              <span class="input-group-text" style="min-width: 135px;">CancelUrl</span>
            </div>
            <input type="text" name="cancelUrl" class="form-control" placeholder="Fill in to overwrite cancelUrl (Online Only)">
          </div>
          <div class="input-group input-group-sm">
            <div class="input-group-prepend">
              <span class="input-group-text" style="min-width: 135px;">appPackageName</span>
            </div>
            <input type="text" name="appPackageName" class="form-control" placeholder="redirectUrls.appPackageName (Online Only)">
          </div>
        </div>
        <hr>
        <div class="input-group input-group-sm">
          <div class="input-group-prepend">
            <span class="input-group-text" style="min-width: 120px;">DeviceProfileId</span>
          </div>
          <input type="text" name="merchantDeviceProfileId" class="form-control"  placeholder="X-LINE-MerchantDeviceProfileId (Alphanumeric Only)">
        </div>
        <div class="input-group input-group-sm">
          <div class="input-group-prepend">
            <label class="input-group-text" style="min-width: 120px;">BranchName</label>
          </div>
          <input type="text" name="branchName" class="form-control" id="" placeholder="options.extra.branchName">
        </div>
        <div class="input-group input-group-sm">
          <div class="input-group-prepend">
            <label class="input-group-text" for="inputConfirmUrlType" style="min-width: 120px;">ConfirmUrlType</label>
          </div>
          <select class="custom-select" id="inputConfirmUrlType" name="confirmUrlType">
            <option value="" selected>Default (Online Only)</option>
            <option value="CLIENT">CLIENT</option>
            <option value="SERVER">SERVER (Only support for listed merchants)</option>
            <option value="NONE">NONE</option>
          </select>
        </div>
        <div class="input-group input-group-sm">
          <div class="input-group-prepend">
            <label class="input-group-text" for="inputLocale" style="min-width: 120px;">Locale</label>
          </div>
          <select class="custom-select" id="inputLocale" name="locale">
            <option value="" selected>Default (Online Only)</option>
            <option value="en">en</option>
            <option value="ja">ja</option>
            <option value="ko">ko</option>
            <option value="th">th</option>
            <option value="zh_TW">zh_TW</option>
            <option value="zh_CN">zh_CN</option>
          </select>
        </div>
        <div class="form-check">
          <input type="checkbox" class="form-check-input" id="inputCheckConfirmUrlBrowser" name="checkConfirmUrlBrowser" <?=!$config || (isset($config['checkConfirmUrlBrowser']) && $config['checkConfirmUrlBrowser']) ? 'checked' : ''?>>
          <label class="form-check-label" for="inputCheckConfirmUrlBrowser">checkConfirmUrlBrowser: <code>true</code> <font color="#cccccc"><i>(Online Only)</i></font></label>
        </div>
        <div class="form-check">
          <input type="checkbox" class="form-check-input" id="inputPaymentUrlApp" name="paymenUrlApp" <?=isset($config['paymenUrlApp']) ? 'checked' : ''?>>
          <label class="form-check-label" for="inputPaymentUrlApp">paymentUrl: <code>app</code> <font color="#cccccc"><i>(Online Only)</i></font></label>
        </div>
        <hr>
        <label>POINT Limit: <code>promotionRestriction</code></label>
        <div class="input-group input-group-sm">
          <div class="input-group-prepend">
            <span class="input-group-text" style="min-width: 120px;">UseLimit</span>
          </div>
          <input type="number" name="useLimit" class="form-control" placeholder="options.extra.promotionRestriction.useLimit">
        </div>
        <div class="input-group input-group-sm">
          <div class="input-group-prepend">
            <span class="input-group-text" style="min-width: 120px;">RewardLimit</span>
          </div>
          <input type="number" name="rewardLimit" class="form-control" placeholder="options.extra.promotionRestriction.rewardLimit">
        </div>
        <hr>
        <div class="row">
          <div class="col col-9 col-md-9">
            <label>Events Code: <code>events</code> <font color="#cccccc"><i></i></font></label>
          </div>
          <div class="col col-3 col-md-3">
            <button class="btn btn-sm btn-outline-secondary float-right btn-events-code-add" type="button">Add</button>
          </div>
        </div>
        <div id="eventsCodeBlock">
        </div>
        <hr>
        <div class="form-group">
          <label>Search Transaction</label>
          <div class="input-group">
            <input type="text" class="form-control" name="transactionId" placeholder="Input transactionId to search">
            <div class="input-group-append">
              <button class="btn btn-outline-secondary" type="submit">Submit</button>
            </div>
          </div>
        </div>
        <hr>
        <div class="form-group">
          <label>Get User Info</label>
          <div class="input-group">
            <input type="text" class="form-control" name="transactionIdForUserInfo" placeholder="Input transactionId to search">
            <div class="input-group-append">
              <button class="btn btn-outline-secondary" type="submit">Submit</button>
            </div>
          </div>
        </div>
        <hr>
        <div class="form-group">
          <label>Rewrite Request Body <font color="#cccccc"><i>(JSON string will be decoded then encoded)</i></font></label>
          <div class="input-group">
            <textarea class="form-control" name="requestBody" id="" rows="4" style="font-size: 9pt;"></textarea>
          </div>
        </div>
      </div>
    </div>
    <hr>
    <div class="row">
      <div class="col col-12 col-md-4" style="padding-bottom:5px;">
        <button type="submit" class="btn btn-primary btn-block">Create New Order</button>
      </div>
      <!-- <div class="col col-12 col-md-8 text-right" style="padding-bottom:5px;">
        <?php if(isset($order['isSuccessful'])):?><a href="./index.php?route=order" class="btn btn-info">Check Last Order</a><?php endif ?>
        <button type="reset" class="btn btn-success">Reset</button>
        <button type="button" class="btn btn-danger" onclick="if(confirm('Confirm to clear saved form data?')){location.href='?route=clear'}">Clear</button>
      </div> -->
      <div class="col col-12 col-md-4" style="padding-bottom:5px;">
      <?php if(isset($order['isSuccessful'])):?>
        <a href="./index.php?route=order" class="btn btn-info btn-block">Review Last Order</a>
      <?php elseif(isset($order['transactionId'])): ?>
        <a href="./check.php?transactionId=<?=$order['transactionId']?>" class="btn btn-info btn-block">Check Order Status</a>
      <?php endif ?>
      </div>
      <div class="col col-12 col-md-2" style="padding-bottom:5px;">
        <button type="reset" class="btn btn-success btn-block">Reset</button>
      </div>
      <div class="col col-12 col-md-2" style="padding-bottom:5px;">
        <button type="button" class="btn btn-danger btn-block" onclick="if(confirm('Confirm to clear saved form data?')){location.href='?route=clear'}">Clear</button>
      </div>
    </div>
  </form>

  <!-- Template -->
  <script type="text/template" id="eventsCodeTemplate">
    <div class="input-group input-group-sm">
      <div class="input-group-prepend">
        <span class="input-group-text" style="min-width: 120px;">Code</span>
      </div>
      <input type="text" name="eventsCode[]" class="form-control" placeholder="extras.events.code">
    </div>
    <div class="input-group input-group-sm">
      <div class="input-group-prepend">
        <span class="input-group-text" style="min-width: 120px;">TotalAmount</span>
      </div>
      <input type="number" name="eventsTotalAmount[]" class="form-control" placeholder="extras.events.totalAmount">
    </div>
    <div class="input-group input-group-sm">
      <div class="input-group-prepend">
        <span class="input-group-text" style="min-width: 120px;">Quantity</span>
      </div>
      <input type="number" name="eventsProductQuantity[]" class="form-control" placeholder="extras.events.productQuantity">
    </div>
  </script>
  <!-- /Template -->

  <?php endif ?>

  <!-- Modal for log -->
  <div class="modal fade" id="logModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Log (Reset by New Order)</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
        <?php foreach ((array) $logs as $key => $log): ?>
          <?php if($key!==0):?>
          <hr>
          <?php endif ?>
          <div>
            <p><strong><?=$log['name']?></strong> (<?=$log['datetime']?>)</p>
            <div class="alert alert-light small" role="alert">
              <strong><?=$log['method']?></strong> <?=$log['uri']?><br>
              <strong>TransferTime</strong>: <?=$log['transferTime']?> s
            </div>
            <p>Request body: <small>(<?=$log['request']['datetime']?>)</small></p>
            <pre class="log"><?=$log['request']['content']?></pre>
            <p>Response body: <small>(<?=$log['response']['datetime']?>)</small></p>
            <pre class="log"><?=$log['response']['content']?></pre>
          </div>
        <?php endforeach ?>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

</div>
<script src="https://code.jquery.com/jquery-3.4.1.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.2.0/crypto-js.min.js" integrity="sha512-a+SUDuwNzXDvz4XrIcXHuCf089/iJAoN4lmrXJg18XnduKK6YlDHNRalv4yd1N40OKI80tFidF+rqTFKGPoWFQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>

  // Merchant config block
  var elBlockConfig = document.querySelector(".merchant-block[data-block-id='config']");

  // jQuery asset loading precaution (ensure that general functionality is available without jQuery)
  if (typeof $ === 'undefined' && elBlockConfig) {
    elBlockConfig.parentNode.removeChild(elBlockConfig);
  }

  // Merchant switch (jQuery required)
  if (elBlockConfig) {

    $(".btn-merchant-switch").click(function () {
      var self = $(this).data("block-id");
      var target = (self==="custom") ? 'config' : 'custom';
      var $selfBlock = $(".merchant-block[data-block-id='" + self + "']");
      var $targetBlock = $(".merchant-block[data-block-id='" + target + "']");
      // Switch
      $selfBlock.find("input, select").prop('disabled', true);
      $selfBlock.hide(300, function () {
        $targetBlock.find("input, select").prop('disabled', false);
        $targetBlock.show(200);
      });
    });
  }

  // EventCode Add Button
  $(".btn-events-code-add").click(function () {
    $("#eventsCodeBlock").append($("#eventsCodeTemplate").html()).append("<br>");
  });

  <?php if($merchants && (!$config || isset($config['merchant']))): ?>
  // Action for merchant config condition
  $(".merchant-block[data-block-id='custom']").find(".btn-merchant-switch").click();
  <?php endif ?>

  // Singature Tool
  $(".btn-sinature-generate").click(function () {
    var secret = $("[name='signature-secret']").val();
    var path = $("[name='signature-path']").val();
    var body = $("[name='signature-body']").val();
    var nonce = $("[name='signature-nonce']").val();
    var signature = CryptoJS.HmacSHA256(secret + path + body + nonce, secret).toString(CryptoJS.enc.Base64);
    $("[name='signature-value']").val(signature);
  });

</script>
</body>
</html>