<h2>{$product}</h2>

<hr>

<div class="row">
    <div class="col-sm-4">
        <a target="_blank" href="https://{$serverhostname}" class="btn btn-info btn-block">
            <i class="fa fa-external-link"></i> Login to video platform
        </a>
    </div>

    {if $packagesupgrade}
        <div class="col-sm-4">
            <a href="upgrade.php?type=package&amp;id={$id}" class="btn btn-success btn-block">
                {$LANG.upgrade}
            </a>
        </div>
    {/if}
</div>

<br />

<h3>Payment</h3>
<hr />


{if $suspendreason}
    <div class="alert alert-danger">
        {$suspendreason}
    </div>
{/if}
{if $pendingcancellation}
    <div class="alert alert-info">
        {$LANG.cancellationrequested}
    </div>
{/if}


<div class="row">
    <div class="col-sm-5">
        {$LANG.clientareastatus}
    </div>
    <div class="col-sm-7">
        {$status}
    </div>
</div>


<div class="row">
    <div class="col-sm-5">
        {$LANG.clientareahostingregdate}
    </div>
    <div class="col-sm-7">
        {$regdate}
    </div>
</div>

<div class="row">
    <div class="col-sm-5">
        {$LANG.orderpaymentmethod}
    </div>
    <div class="col-sm-7">
        {$paymentmethod}
    </div>
</div>

<div class="row">
    <div class="col-sm-5">
        {$LANG.firstpaymentamount}
    </div>
    <div class="col-sm-7">
        {$firstpaymentamount}
    </div>
</div>

<div class="row">
    <div class="col-sm-5">
        {$LANG.recurringamount}
    </div>
    <div class="col-sm-7">
        {$recurringamount}
    </div>
</div>

<div class="row">
    <div class="col-sm-5">
        {$LANG.clientareahostingnextduedate}
    </div>
    <div class="col-sm-7">
        {$nextduedate}
    </div>
</div>

<div class="row">
    <div class="col-sm-5">
        {$LANG.orderbillingcycle}
    </div>
    <div class="col-sm-7">
        {$billingcycle}
    </div>
</div>

<br />

{if $metricStats}
    <h3>Usage</h3>
    <hr />
    {include file="$template/clientareaproductusagebilling.tpl"}
{/if}
<br />
<br />
