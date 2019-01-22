<h1>Contacts</h1>
<div class="fpbx-container container-fluid">
    <div class="row">
        <div class="col-sm-12">
			<div class="display full-border">
				<div class="tab-pane active">
					<h2>Add / Edit Contact</h2>
					<div id="toolbar-all" class="fixed-table-toolbar">
						<a href="config.php?display=contacts" class="btn btn-default">Contacts</a>
					</div>
					<form autocomplete="off" name="edit" id="editContact" action="" method="post" class="fpbx-submit" data-fpbx-delete="<?php echo $urls['deleteContactsAjax'], '&id=', $contact['id']; ?>" >
						<input type="hidden" name="contact[id]" value="<?php echo (string)$contact['id']; ?>">
						   <!--First Name-->
							<div class="element-container">
								<div class="row">
									<div class="col-md-12">
										<div class="row">
											<div class="form-group">
												<div class="col-md-3">
													<label class="control-label" for="firstname"><?php echo _("First Name") ?></label>
													<i class="fa fa-question-circle fpbx-help-icon" data-for="firstname"></i>
												</div>
												<div class="col-md-9">
													<input type="text" class="form-control maxlen" id="firstname" maxlength="25" name="contact[firstname]" value="<?php echo $contact['FirstName']; ?>" pattern="<?php echo $regexes['firstname']; ?>">
												</div>
											</div>
										</div>
									</div>
								</div>
								<div class="row">
									<div class="col-md-12">
										<span id="firstname-help" class="help-block fpbx-help-block"><?php echo _("The contact's first name.")?></span>
									</div>
								</div>
							</div>
							<!--END First name-->
							<!--Last Name-->
							<div class="element-container">
								<div class="row">
									<div class="col-md-12">
										<div class="row">
											<div class="form-group">
												<div class="col-md-3">
													<label class="control-label" for="lastname"><?php echo _("Last Name") ?></label>
													<i class="fa fa-question-circle fpbx-help-icon" data-for="lastname"></i>
												</div>
												<div class="col-md-9">
													<input type="text" class="form-control maxlen" id="lastname" maxlength="25" name="contact[lastname]" value="<?php echo $contact['LastName']; ?>" pattern="<?php echo $regexes['lastname']; ?>">
												</div>
											</div>
										</div>
									</div>
								</div>
								<div class="row">
									<div class="col-md-12">
										<span id="lastname-help" class="help-block fpbx-help-block"><?php echo _("The contact's last name.")?></span>
									</div>
								</div>
							</div>
							<!--END Last Name-->
							<!--Organisation-->
							<div class="element-container">
								<div class="row">
									<div class="col-md-12">
										<div class="row">
											<div class="form-group">
												<div class="col-md-3">
													<label class="control-label" for="organisation"><?php echo _("Organisation") ?></label>
													<i class="fa fa-question-circle fpbx-help-icon" data-for="organisation"></i>
												</div>
												<div class="col-md-9">
													<input type="text" min="0" class="form-control maxlen" id="organisation" maxlength="50"  name="contact[organisation]" value="<?php echo $contact['Organisation']; ?>" pattern="<?php echo $regexes['organisation']; ?>">
												</div>
											</div>
										</div>
									</div>
								</div>
								<div class="row">
									<div class="col-md-12">
										<span id="organisation-help" class="help-block fpbx-help-block"><?php echo _("The organisation which the contact is associated with.")?></span>
									</div>
								</div>
							</div>
							<!--END Organisation-->
							<!--Landline-->
							<div class="element-container">
								<div class="row">
									<div class="col-md-12">
										<div class="row">
											<div class="form-group">
												<div class="col-md-3">
													<label class="control-label" for="landline"><?php echo _("Landline") ?></label>
													<i class="fa fa-question-circle fpbx-help-icon" data-for="landline"></i>
												</div>
												<div class="col-md-9">
													<input type="tel" min="0" class="form-control maxlen nowhitespace" id="landline" maxlength="25" name="contact[landline]" value="<?php echo $contact['Landline']; ?>"
														placeholder="(+XXX)XXXXXXXX		No Spaces, Area code optional"
														pattern="<?php echo $regexes['landline']; ?>">
												</div>
											</div>
										</div>
									</div>
								</div>
								<div class="row">
									<div class="col-md-12">
										<span id="landline-help" class="help-block fpbx-help-block"><?php echo _("A landline phone number associated with the contact (with area prefix).")?></span>
									</div>
								</div>
							</div>
							<!--END Landline-->
							<!--Mobile Phone-->
							<div class="element-container">
								<div class="row">
									<div class="col-md-12">
										<div class="row">
											<div class="form-group">
												<div class="col-md-3">
													<label class="control-label" for="mobile"><?php echo _("Mobile Phone") ?></label>
													<i class="fa fa-question-circle fpbx-help-icon" data-for="mobile"></i>
												</div>
												<div class="col-md-9">
													<input type="tel" min="0" class="form-control maxlen nowhitespace" id="mobile" maxlength="25" name="contact[mobile]" value="<?php echo $contact['MobilePhone']; ?>"
														placeholder="+614XXXXXXXX		No Spaces"
														pattern="<?php echo $regexes['mobile']; ?>">
												</div>
											</div>
										</div>
									</div>
								</div>
								<div class="row">
									<div class="col-md-12">
										<span id="mobile-help" class="help-block fpbx-help-block"><?php echo _("The mobile phone number associated with the contact.")?></span>
									</div>
								</div>
							</div>
							<!--END Mobile Phone-->
							<!--Email-->
							<div class="element-container">
								<div class="row">
									<div class="col-md-12">
										<div class="row">
											<div class="form-group">
												<div class="col-md-3">
													<label class="control-label" for="email"><?php echo _("Email") ?></label>
													<i class="fa fa-question-circle fpbx-help-icon" data-for="email"></i>
												</div>
												<div class="col-md-9">
													<input type="email" min="0" class="form-control maxlen nowhitespace" id="email" maxlength="75" name="contact[email]" value="<?php echo $contact['Email']; ?>"
														placeholder="person@organisation.tld"
														pattern="<?php echo $regexes['email']; ?>">
												</div>
											</div>
										</div>
									</div>
								</div>
								<div class="row">
									<div class="col-md-12">
										<span id="email-help" class="help-block fpbx-help-block"><?php echo _("The email which the contact is associated with.")?></span>
									</div>
								</div>
							</div>
							<!--END Email-->
					</form>
				</div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $('document').ready(function() {
        $('#editContact').submit(function (event) {
            event.stopPropagation();
            event.preventDefault();
            var dataarr = $('#editContact').serializeArray();
            var data = [];
			var submiturl;
            console.log(dataarr);
            dataarr.forEach ( function(datum) {
                switch (datum['name']) {
                    case 'contact[landline]':
						var llfnum = datum['value'].substring(0,1)
						if (
							datum['value'].length !== 0 &&
							(llfnum == '9' || llfnum == '6')
						) {
							datum['value'] = '08'+datum['value'];
						}
						break;
					case 'contact[id]':
						console.log('contact id');
						if (datum['value'] == -1) {
							submiturl = "<?php echo $urls['addContactAjax']?>";
						} else {
							submiturl = "<?php echo $urls['updateContactAjax']?>";
						}
						break;
				}

                data[data.length] = encodeURIComponent(datum['name'])+'='+encodeURIComponent(datum['value']);
            });
            $.ajax({
                method: "POST",
                url: submiturl,
                data: data.join('&')
            }).success(function( msg, status ) {
				jsonmsg = JSON.parse(msg.message);
				if (jsonmsg[0] == false) {
					alert("Data failed to save.\nMessage from the server:\n"+jsonmsg[1]);
				} else {
					if (jsonmsg[0].length > 0) {
						alert( "Data saved.\nMessage from the server:\n"+jsonmsg[1]);
					} else {
						alert( "Data saved" );
					}
				}
                return false;
            }).fail(function( msg, status ) {
                alert( "Data failed to saved" );
                return false;
            });
        });
		$('input.nowhitespace').focusout(function(event) {
			$(event.target).val($(event.target).val().replace(/ /g,''));
		});
    });
    function deleteContact(event) {
        event.stopPropagation();
        event.preventDefault();
        if ( confirm("Are you sure you would like to delete this contact?") ) {
            $.ajax({
                type: "GET",
                url: '<?php echo $urls['deleteContactsAjax']; ?>&id='+row.id,
                success: function (data) {
                    if ( data.message ) {
                        alert ('We have success!');
                    } else {
                        alert ('Failed to delete the contact');
                    }
                    window.location.href = "<?php echo $urls['displayContactsURL']; ?>";
                },
                error: function (er) {
                    alert('There was a problem trying to get data from the server');
                }
            });
        } else {
            alert('Not deleting contact');
        }
        return ' ';
    }
</script>
