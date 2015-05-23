<h3>Contact Us</h3><br>

<div class="container">
    <div class="row">
        <div>
            <div class="well well-sm">
                <form id="form_contacts">
                    <div class="row">
                        <div class="col-md-5">
                            <div class="form-group">
                                <label for="name">
                                    Name</label>
                                <input type="text" class="form-control" id="name" name="name" placeholder="Enter name"/>
                            </div>
                            <div class="form-group">
                                <label for="email">
                                    Email Address</label>

                                <div class="input-group">

                                    <input type="text" class="form-control" id="email" name="email"
                                           placeholder="Enter email"/>
                                    <span class="input-group-addon"><span
                                            class="glyphicon glyphicon-envelope"></span></span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="subject">
                                    Subject</label>
                                <select id="subject" name="subject" class="form-control">
                                    <option value="general">General Information</option>
                                    <option value="suggestions">Suggestions</option>
                                    <option value="techsupport">Technical Support</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <div class="form-group">
                                <label for="name">
                                    Message</label>
                                <textarea name="message" id="message" name="message" class="form-control" rows="9"
                                          cols="25" placeholder="Message"></textarea>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary pull-right" id="btnContactUs">
                                Send Message
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>


<script>

    $ ('body').on ('submit', 'form#form_contacts', function (event) {
        $ ('div.row').fadeTo (1200, 0.3);

        event.preventDefault ();

        post_data = $ (this).serialize ();

        response = $.ajax (ajax_calls_home + 'contacts', {type: 'POST', data: post_data, dataType: 'json'});
        response.done (function (json_obj) {
            $ ('div.row').fadeTo (50, 1);
            if (json_obj.error == false) {
                toastr["success"] ('We received your message!<br>Thanks', 'CONTACT FORM');
                $ ('form#form_contacts').trigger ('reset')
                //window.location = '/useractivation/'
            }
            else {
                if ($.isArray (json_obj.error_descr)) {
                    toastr["error"] (json_obj.error_descr.join ('<br>'), 'CONTACT FORM');
                }
                else {
                    toastr["error"] (json_obj.error_descr, 'CONTACT FORM');
                }
            }

        });// END DONE METHOD

    }); //END FORM SUBMIT HANDLER


</script>