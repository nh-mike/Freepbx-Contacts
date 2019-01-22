<h1>Contacts</h1>
<div class="fpbx-container container-fluid">
	<div class="row">
        <div class="col-sm-12">
            <div class="display full-border">
				<div class="tab-pane active">
					<div id="toolbar-all">
						<a href="config.php?display=contacts&command=addContact" class="btn btn-default">New Contact</a>
						<a href="config.php?display=contacts&command=recreateXml" class="btn btn-default">Recreate XML Addressbook</a>
						<a href="config.php?display=contacts&command=reloadLdap" class="btn btn-default">Reload LDAP Database</a>
					</div>
					<table id="table-all"
						data-url="<?php echo $urls['getContactsAjax']; ?>"
						data-cache="false"
						data-cookie="true"
						data-cookie-id-table="contacts_table"
						data-toolbar="#toolbar-all"
						data-maintain-selected="true"
						data-show-columns="true"
						data-show-toggle="true"
						data-sortable="true"
						data-pagination="true"
						data-search="true"
						class="table table-striped">
						<thead>
							<tr>
								<th data-field="FirstName"><?php echo _("First Name");?></th>
								<th data-field="LastName"><?php echo _("Last Name");?></th>
								<th data-field="Organisation"><?php echo _("Organisation");?></th>
								<th data-field="Landline"><?php echo _("Landline");?></th>
								<th data-field="Mobile"><?php echo _("Mobile");?></th>
								<th data-field="Email"><?php echo _("Email");?></th>
								<th><?php echo _("Actions");?></th>
							</tr>
						</thead>
					</table>
				</div>
            </div>
        </div>
    </div>
</div>


<script type="text/javascript">
    $('document').ready(function(){
        $.ajax({
            type: "GET",
            url: $('#table-all').attr('data-url'),
            success: function (data) {
                $('#table-all').bootstrapTable({
                    data: JSON.parse(data.message),
                    columns: [
                        {
                            field: 'id',
                            title: 'id',
                            sortable: false,
                        },{
                            field: 'FirstName',
                            title: 'First Name',
                            sortable: true,
                            align: 'left'
                        },{
                            field: 'LastName',
                            title: 'Last Name',
                            sortable: true,
                            align: 'left'
                        },{
                            field: 'Organisation',
                            title: 'Organisation',
                            sortable: true,
                            align: 'left'
                        },{
                            field: 'Landline',
                            title: 'Landline',
                            sortable: true,
                            align: 'left'
                        },{
                            field: 'Mobile',
                            title: 'Mobile Phone',
                            sortable: true,
                            align: 'left'
                        },{
                            field: 'Email',
                            title: 'Email',
                            sortable: true,
                            align: 'left'
                        },{
                            field: 'Actions',
                            title: 'Actions',
                            sortable: false,
                            align: 'left',
                            events: operateEvents,
                            formatter: operateFormatter
                        }
                    ]
                });
                $('#table-all').bootstrapTable('hideColumn', 'id');
//                $('.th-inner').addClass('sortable both');
            },
            error: function (er) {
                alert('There was a problem trying to get data from the server');
            }
        });
    });

    function operateFormatter(value, row, index) {
        return [
            '<a class="edit" href="javascript:void(0)" title="Edit">',
            '<i class="fa fa-edit"></i>',
            '</a>  ',
            '<a class="delete" href="javascript:void(0)" title="Delete">',
            '<i class="fa fa-trash"></i>',
            '</a>'
        ].join('');
    }

    window.operateEvents = {
        'click .edit': function (e, value, row, index) {
            window.location.href = '<?php echo $urls['editContactURL']; ?>'+row.id;
        },
        'click .delete': function (e, value, row, index) {
            var fname = (row.FirstName.length > 0 ? row.FirstName + ' ' : '') ;
            var lname = (row.LastName.length > 0 ? row.LastName + ' ' : '');
            if ( row.Organisation.length > 0 ) {
                if ( (fname.length + lname.length) > 0 ) {
                    var orgname = 'from ' + row.Organisation;
                } else {
                    var orgname = row.Organisation;
                }
            } else {
                var orgname = '';
            }
            if ( confirm("Are you sure you would like to delete "+fname+lname+orgname) ) {
/*                $.ajax({
                    type: "GET",
                    url: '<?php echo $urls['deleteContactsAjax']; ?>&id='+row.id,
                    success: function (data) {
                        if ( data.message ) {
                            alert ('We have success!');
                            window.location.href = "<?php echo $urls['displayContactsURL']; ?>";
                        } else {
                            alert ('Failed to delete the contact');
                        }
                    },
                    error: function (er) {
                        alert('There was a problem trying to get data from the server');
                    }
                });*/
                window.location.href = '<?php echo $urls['deleteContactsAjax']; ?>&id='+row.id;
            } else {
                alert('Not deleting contact');
            }
        }
    };

/*    function linkFormatter(value, row, index){
        var html = '<a href="?display=contacts&view=form&id='+value+'"><i class="fa fa-pencil"></i></a>';
        html += '&nbsp;<a href="?display=contacts&action=delete&id='+value+'" class="delAction"><i class="fa fa-trash"></i></a>';
        return html;
    } */

    $("table").on("post-body.bs.table", function () {
        $(".deleteitem").off("click");
        $(".deleteitem").click(function(e) {
            if(!confirm(_("Are you sure you want to delete this flow?"))) {
                e.preventDefault();
                e.stopPropagation();
            }
        });
    });
</script>
