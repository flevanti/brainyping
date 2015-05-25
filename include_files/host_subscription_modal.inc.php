<!-- Modal -->
<div class="modal fade" id="modal_subscribe" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"
     aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel">MONITOR ALERT</h4>
            </div>
            <div class="modal-body">
                ...
            </div>
            <div class="modal-footer">
                <span class="micro_text">No registration required.<br>We will <b>never</b> give these information to others</span>
            </div>
        </div>
    </div>
</div>


<script>
    var public_token_subscribe = '';

    function showSubscriptionModal () {
        $ ('span#star_subscribe').trigger ('click');
    }

    //HANDLER TO ACTIVATE MODAL (THIS IS FOR HOMEPAGE)
    $ ('body').on ('click', 'p.tile_star_subscribe', function (e) {
        e.stopPropagation ();
        $ (this).tooltip ('hide');
        public_token_subscribe = $ (this).attr ('host_public_token');
        $ ('#modal_subscribe').modal ({
            keyboard: false,
            backdrop: 'static'
        });
    });

    //HANDLER TO ACTIVATE MODAL (THIS IS FOR INFO PAGE)
    $ ('body').on ('click', 'span#star_subscribe', function (e) {
        e.stopPropagation ();
        $ (this).tooltip ('hide');
        public_token_subscribe = $ (this).attr ('host_public_token');
        $ ('#modal_subscribe').modal ({
            keyboard: false,
            backdrop: 'static'
        });
    });

    $ ('#modal_subscribe').on ('show.bs.modal', function (e) {
        $ ('#modal_subscribe .modal-body').html ('LOADING....');
    });
    $ ('#modal_subscribe').on ('shown.bs.modal', function (e) {
        $ ('#modal_subscribe .modal-body').load ('/index_ajax_calls.php/subscription_modal_form/' + public_token_subscribe, function () {
            $ ('input#email_subscription').focus ();
        });
    });

    $ ('body').on ('click', 'button#confirm_subscription', function () {
        post_data = $ ('form#form_subscription').serialize ();

        $ ('span#error_area').html ('<img src="/imgs/preloader2.gif">');

        $.ajax ({
            type : "POST",
            data : post_data,
            url  : ajax_calls_home + 'subscription_save/',
            cache: false
        })
            .done (function (result) {
                try {
                    result = $.parseJSON (result);
                } catch (err) {
                    $ ('span#error_area').html ('Error during JSON operation');
                    return false;
                }
                if (result["error"] == false) {
                    contact = $ ('form#form_subscription #email_subscription').val ();
                    if (contact == undefined) {
                        contact = "Thanks!";
                        $ ('#modal_subscribe .modal-body').html ('<b>' + contact + '</b><br>YOUR ALERT HAS BEEN CREATED!!<br><br>You can manage monitor subscriptions in you Profile Section<br><br><button type="button" class="btn btn-info" id="close_modal_subscription">OK, GOT IT!</button><br>');
                    }
                    else {
                        $ ('#modal_subscribe .modal-body').html ('<b>' + contact + '</b><br>YOUR ALERT HAS BEEN CREATED!!<br><br>To activate it click the link in the email you will receive within few minutes.<br><br><button type="button" class="btn btn-info" id="close_modal_subscription">OK, GOT IT!</button><br><span class="micro_text">This is to avoid spam and be sure this is you :)</span>');
                    }

                }
                else {
                    $ ('span#error_area').html (result["error_descr"]);
                    return false;
                }
            });

    });

    $ ('body').on ('click', 'button#close_modal_subscription', function () {
        $ ('#modal_subscribe').modal ('hide');
    });

    $ ('body').on ('submit', 'form#form_subscription', function (event) {
        event.preventDefault ();
    });

    $ (document).ready (function () {
        if (window.location.hash == '#showsubscrmodal') {
            showSubscriptionModal ();
        }
    })

</script>