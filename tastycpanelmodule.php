<?php

class Tastycpanelmodule extends Module {

    private static $version = "2.4.0";
    private static $authors = array(array('name' => "ModulesBakery.com.", 'url' => "http://www.modulesbakery.com"));

    public function __construct() {
        Loader::loadComponents($this, array("Input", "Record"));
        Language::loadLang("tastycpanel_lang", null, dirname(__FILE__) . DS . "language" . DS);
        Loader::loadHelpers($this, array("Html"));
    }

    public function getName() {
        return Language::_("tastycpanel.name", true);
    }

    public function getVersion() {
        return self::$version;
    }

    public function getAuthors() {
        return self::$authors;
    }

    public function getAdminTabs($package) {
        return array(
            'admin_accountusage' => Language::_("tastycpanel.accountusage", true),
        );
    }

    public function customStyleJS() {
        $webdir = WEBDIR;
        return "\n<script type='text/javascript' src='{$webdir}components/modules/tastycpanelmodule/views/default/main.js'></script>
            \n<link href='{$webdir}components/modules/tastycpanelmodule/views/default/style.css' rel='stylesheet' type='text/css' />
            ";
    }

    public function getClientTabs($package) {
        $clienttabs = array();
//        if ($package->meta->type === "reseller") {
//            $clienttabs['listaccounts'] = array(
//                "name" => Language::_("tastycpanel.reselleraccount", true),
//                "icon" => "fa fa-users",
//            );
//        }
        if ($package->meta->accountusage === "true") {
            $clienttabs['accountusage'] = array(
                "name" => Language::_("tastycpanel.accountusage", true),
                "icon" => "fa fa-bar-chart",
            );
        }
        if ($package->meta->changepassword === "true") {
            $clienttabs['changepassword'] = array(
                "name" => Language::_("tastycpanel.changepassword", true),
                "icon" => "fa fa-key",
            );
        }
        if ($package->meta->email === "true") {
            $clienttabs['email'] = array(
                "name" => Language::_("tastycpanel.email", true),
                "icon" => "fa fa-at",
            );
        }
        if ($package->meta->emailforwarders === "true") {
            $clienttabs['emailforwarders'] = array(
                "name" => Language::_("tastycpanel.emailforwarders", true),
                "icon" => "fa fa-share",
            );
        }
        if ($package->meta->ftpaccounts === "true") {
            $clienttabs['ftpaccounts'] = array(
                "name" => Language::_("tastycpanel.ftpaccounts", true),
                "icon" => "fa fa-upload",
            );
        }
        if ($package->meta->subdomains === "true") {
            $clienttabs['subdomains'] = array(
                "name" => Language::_("tastycpanel.subdomains", true),
                "icon" => "fa fa-globe",
            );
        }
        if ($package->meta->addondomains === "true") {
            $clienttabs['addondomains'] = array(
                "name" => Language::_("tastycpanel.addondomains", true),
                "icon" => "fa fa-globe",
            );
        }
        if ($package->meta->parkeddomains === "true") {
            $clienttabs['parkeddomains'] = array(
                "name" => Language::_("tastycpanel.parkeddomains", true),
                "icon" => "fa fa-globe",
            );
        }
        if ($package->meta->databases === "true") {
            $clienttabs['databases'] = array(
                "name" => Language::_("tastycpanel.databases", true),
                "icon" => "fa fa-database",
            );
        }
        if ($package->meta->cronjobs === "true") {
            $clienttabs['cronjobs'] = array(
                "name" => Language::_("tastycpanel.cronjobs", true),
                "icon" => "fa fa-list-alt",
            );
        }
        if ($package->meta->backups === "true") {
            $clienttabs['backups'] = array(
                "name" => Language::_("tastycpanel.backups", true),
                "icon" => "fa fa-hdd-o",
            );
        }
        if ($package->meta->ipblocker === "true") {
            $clienttabs['ipblocker'] = array(
                "name" => Language::_("tastycpanel.ipblocker", true),
                "icon" => "fa fa-ban",
            );
        }
        if ($package->meta->firewall !== "false") {
            $clienttabs['firewall'] = array(
                "name" => Language::_("tastycpanel.firewall", true),
                "icon" => "fa fa-cogs",
            );
        }
        if ($package->meta->manageapps !== "false") {
            $clienttabs['manageapps'] = array(
                "name" => Language::_("tastycpanel.manageapps", true),
                "icon" => "fa fa-cogs",
            );
        }

        return $clienttabs;
    }

    private function addMBs($email_list) {
        foreach ($email_list->cpanelresult->data as $key => $value) {
            if ($email_list->cpanelresult->data[$key]->diskused !== "unlimited") {
                $email_list->cpanelresult->data[$key]->diskused = $email_list->cpanelresult->data[$key]->diskused . " MB";
            }
            if ($email_list->cpanelresult->data[$key]->diskquota !== "unlimited") {
                $email_list->cpanelresult->data[$key]->diskquota = $email_list->cpanelresult->data[$key]->diskquota . " MB";
            }
        }
        return $email_list;
    }

    public function emailmain($package, $service, array $get = null, array $post = null, array $files = null) {
        $customfiles = $this->customStyleJS();
        $this->view = new View("email", "default");
        $this->view->base_uri = $this->base_uri;
        Loader::loadHelpers($this, array("Form", "Html"));
        $service_fields = $this->serviceFieldsToObject($service->fields);
        $module_row = $this->getModuleRow($package->module_row);
        $cpanel = $this->getcPanelApi($module_row->meta->accesskey, $module_row->meta->hostname, $module_row->meta->username, $module_row->meta->use_ssl, false);

        if (isset($post['delete_email'])) {
            if (!empty($post['domain']) && !empty($post['email'])) {
                $delete_email = $cpanel->api2_query($service_fields->username, "Email", "delpop", $post);
                $this->log($module_row->meta->hostname . "|Delete Email Account", serialize("delpop"), "input", true);
                if (isset($delete_email->result) && $delete_email->result == 0) {
                    $error = array(
                        0 => array(
                            "result" => $delete_email->reason
                        )
                    );
                    $this->Input->setErrors($error[0]);
                }
            } else {
                $error = array(
                    0 => array(
                        "result" => Language::_("tastycpanel.empty_invalid_values", true)
                    )
                );
                $this->Input->setErrors($error[0]);
            }
        }





        $email_list = $cpanel->api2_query($service_fields->username, "Email", "listpopswithdisk", array());
        $this->view->set("module_row", $module_row);
        $this->view->set("service_fields", $service_fields);
        $this->view->set("email_list", $this->addMBs($email_list)->cpanelresult->data);
        $this->view->set("type", $package->meta->type);
        $this->view->set("service_id", $service->id);



        $this->view->setDefaultView("components" . DS . "modules" . DS . "tastycpanelmodule" . DS);
        return $customfiles . $this->view->fetch();
    }

    public function emailchangequota($package, $service, array $get = null, array $post = null, array $files = null) {

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            if (isset($get["domain"]) && isset($get["email"])) {
                Loader::loadHelpers($this, array("Form", "Html"));
                $service_fields = $this->serviceFieldsToObject($service->fields);
                $module_row = $this->getModuleRow($package->module_row);
                $cpanel = $this->getcPanelApi($module_row->meta->accesskey, $module_row->meta->hostname, $module_row->meta->username, $module_row->meta->use_ssl, false);
                if (isset($post) && !empty($post)) {
                    if ($post['quota'] !== "" && $post['email'] !== "" && $post['domain'] !== "") {
                        Loader::loadModels($this, array("Services"));
                        $change_quota = $cpanel->api2_query($service_fields->username, "Email", "editquota", $post);
                        $this->log($module_row->meta->hostname . "|Change Email Quota", serialize("editquota"), "input", true);
                        if (isset($change_quota->cpanelresult->error) && !empty($change_quota->cpanelresult->error)) {
                            $error = array(
                                0 => array(
                                    "result" => $change_quota->cpanelresult->error
                                )
                            );
                            echo "<div class='alert alert-danger alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>{$error[0]['result']}</p>
			</div>";
                        } else {
                            echo "<div class='alert alert-success alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>" . Language::_("tastycpanel.success", true) . "</p>
			</div><script>$(document).ready(function (e) { $('#global_modal').modal('hide');});</script>";
                        }
                    } else {
                        $error = array(
                            0 => array(
                                "result" => Language::_("tastycpanel.empty_invalid_values", true)
                            )
                        );
                        echo "<div class='alert alert-danger alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>{$error[0]['result']}</p>
			</div>";
                    }
                } else {
                    $customfiles = $this->customStyleJS();

                    $this->Form->create("", array('onsubmit' => 'return false', 'id' => 'changequotaform', 'autocomplete' => "off"));
                    echo" {$customfiles}
            <div class='modal-body'>
            <div class='div_response'></div>";
                    $this->Form->fieldHidden("email", $this->Html->ifSet($get["email"]), array('id' => "email"));
                    $this->Form->fieldHidden("domain", $this->Html->ifSet($get["domain"]), array('id' => "domain"));

                    echo'<div class="form-group">
   <label>' . Language::_("tastycpanel.email.changequota", true) . " " . Language::_("tastycpanel.for", true) . " " . $get["email"] . "@" . $get["domain"] . '</label>
    <input type="text" class="form-control" id="quota" name="quota" placeholder="e.g: 250 OR Unlimited">
</div>
</div>
<div class="modal-footer">
<button type="button" name="cancel" class="btn btn-default" data-dismiss="modal"><i class="fa fa-ban"></i> ' . Language::_("tastycpanel.cancel", true) . '</button>
<button type="button" class="btn btn-primary" name="change_quota" id="change_quota"><i class="fa fa-edit"></i> ' . Language::_("tastycpanel.submit", true) . '</button>
</div>
<script type="text/javascript">
    $(document).ready(function () {
        $("#change_quota").click(function () {
    var form = $("#changequotaform").serialize();
   doAjaxPost("' . $this->base_uri . "services/manage/" . $service->id . "/email/changequota/?" . '"+ form, form);
        });
    });
</script>


    ';
                    $this->Form->end();
                }

                exit();
            } else {
                return false;
            }
        }
    }

    public function emailchangepassword($package, $service, array $get = null, array $post = null, array $files = null) {

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            if (isset($get["domain"]) && isset($get["email"])) {
                Loader::loadHelpers($this, array("Form", "Html"));
                $service_fields = $this->serviceFieldsToObject($service->fields);
                $module_row = $this->getModuleRow($package->module_row);
                $cpanel = $this->getcPanelApi($module_row->meta->accesskey, $module_row->meta->hostname, $module_row->meta->username, $module_row->meta->use_ssl, false);
                if (isset($post) && !empty($post)) {
                    if ($post['password'] !== "" && $post['email'] !== "" && $post['domain'] !== "") {
                        Loader::loadModels($this, array("Services"));
                        $change_password = $cpanel->api2_query($service_fields->username, "Email", "passwdpop", $post);
                        $this->log($module_row->meta->hostname . "|Change Email Password", serialize("passwdpop"), "input", true);
                        if (isset($change_password->cpanelresult->error) && !empty($change_password->cpanelresult->error)) {
                            $error = array(
                                0 => array(
                                    "result" => $change_password->cpanelresult->error
                                )
                            );
                            echo "<div class='alert alert-danger alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>{$error[0]['result']}</p>
			</div>";
                        } else {
                            echo "<div class='alert alert-success alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>" . Language::_("tastycpanel.success", true) . "</p>
			</div><script>$(document).ready(function (e) { $('#global_modal').modal('hide');});</script>";
                        }
                    } else {
                        $error = array(
                            0 => array(
                                "result" => Language::_("tastycpanel.empty_invalid_values", true)
                            )
                        );
                        echo "<div class='alert alert-danger alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>{$error[0]['result']}</p>
			</div>";
                    }
                } else {
                    $customfiles = $this->customStyleJS();

                    $this->Form->create("", array('onsubmit' => 'return false', 'id' => 'changepassform', 'autocomplete' => "off"));
                    echo" {$customfiles}
            <div class='modal-body'>
<div class='div_response'></div>";
                    $this->Form->fieldHidden("email", $this->Html->ifSet($get["email"]), array('id' => "email"));
                    $this->Form->fieldHidden("domain", $this->Html->ifSet($get["domain"]), array('id' => "domain"));

                    echo'<div class="form-group">
   <label>' . Language::_("tastycpanel.changepassword", true) . " " . Language::_("tastycpanel.for", true) . " " . $get["email"] . "@" . $get["domain"] . '</label>
    <input type="password" class="form-control" value="" id="password" name="password" placeholder="**********">
</div>
<div class="new_div"></div>
</div>
<div class="modal-footer">
<button type="button" name="cancel" class="btn btn-default" data-dismiss="modal"><i class="fa fa-ban"></i> ' . Language::_("tastycpanel.cancel", true) . '</button>
<button type="button" class="btn btn-default" id="generate"><i class="fa fa-key"></i> ' . Language::_("tastycpanel.service.generate", true) . '</button>
<button type="button" class="btn btn-primary" name="change_password" id="change_password"><i class="fa fa-edit"></i> ' . Language::_("tastycpanel.submit", true) . '</button>
</div>
<script type="text/javascript">
    $(document).ready(function () {
        $("#change_password").click(function () {
    var form = $("#changepassform").serialize();
    doAjaxPost("' . $this->base_uri . "services/manage/" . $service->id . "/email/changepassword/?" . '"+ form, form);
        });
        $("#generate").click(function () {
            doAjaxGet("' . $this->base_uri . "services/manage/" . $service->id . "/changepassword/generatepass/" . '", "' . Language::_("tastycpanel.generated_password", true) . '");
        });
    });
</script>


    ';
                    $this->Form->end();
                }

                exit();
            } else {
                return false;
            }
        }
    }

    public function emailaddnew($package, $service, array $get = null, array $post = null, array $files = null) {

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            Loader::loadHelpers($this, array("Form", "Html"));
            $service_fields = $this->serviceFieldsToObject($service->fields);
            $module_row = $this->getModuleRow($package->module_row);
            $cpanel = $this->getcPanelApi($module_row->meta->accesskey, $module_row->meta->hostname, $module_row->meta->username, $module_row->meta->use_ssl, false);
            if (isset($post) && !empty($post)) {
                if ($post['password'] !== "" && $post['email'] !== "" && $post['domain'] !== "" && $post['quota'] !== "") {
                    Loader::loadModels($this, array("Services"));
                    $add_new = $cpanel->api2_query($service_fields->username, "Email", "addpop", $post);
                    $this->log($module_row->meta->hostname . "|Add New Email", serialize("addpop"), "input", true);
                    if (isset($add_new->cpanelresult->error) && !empty($add_new->cpanelresult->error)) {
                        $error = array(
                            0 => array(
                                "result" => $add_new->cpanelresult->error
                            )
                        );
                        echo "<div class='alert alert-danger alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>{$error[0]['result']}</p>
			</div>";
                    } else {
                        echo "<div class='alert alert-success alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>" . Language::_("tastycpanel.success", true) . "</p>
			</div><script>$(document).ready(function (e) { $('#global_modal').modal('hide');});</script>";
                    }
                } else {
                    $error = array(
                        0 => array(
                            "result" => Language::_("tastycpanel.empty_invalid_values", true)
                        )
                    );
                    echo "<div class='alert alert-danger alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>{$error[0]['result']}</p>
			</div>";
                }
            } else {
                $customfiles = $this->customStyleJS();
                $get_domains = $cpanel->api2_query($service_fields->username, "Email", "listmaildomains", array());
                $select_option = "<select name='domain' class='form-control' id='domain'>";
                foreach ($get_domains->cpanelresult->data as $key => $value) {
                    $select_option .= "<option value='{$get_domains->cpanelresult->data[$key]->domain}'>{$get_domains->cpanelresult->data[$key]->domain}</option>";
                }
                $select_option .= "</select>";

                $this->Form->create("", array('onsubmit' => 'return false', 'id' => 'addform', 'autocomplete' => "off"));
                echo" {$customfiles}
            <div class='modal-body'>
<div class='div_response'></div>";

                echo'<div class="form-group">
   <label>' . Language::_("tastycpanel.email.email_address", true) . '</label>
    <input type="email" class="form-control" value="" id="email" name="email" placeholder=""></div>
    <div class="form-group">
   <label>' . Language::_("tastycpanel.email.domain", true) . '</label>
   ' . $select_option . '</div>
  <div class="form-group"> <label>' . Language::_("tastycpanel.email.quota", true) . '</label>
    <input type="text" class="form-control" value="" id="quota" name="quota" placeholder="e.g: 250 OR Unlimited"></div>
   <div class="form-group"><label>' . Language::_("tastycpanel.password", true) . '</label>
    <input type="password" class="form-control" value="" id="password" name="password" placeholder="***********"></div>
<div class="new_div"></div>
</div>
</div>
<div class="modal-footer">
<button type="button" name="cancel" class="btn btn-default" data-dismiss="modal"><i class="fa fa-ban"></i> ' . Language::_("tastycpanel.cancel", true) . '</button>
<button type="button" class="btn btn-default" id="generate"><i class="fa fa-key"></i> ' . Language::_("tastycpanel.service.generate", true) . '</button>
<button type="button" class="btn btn-primary" name="add_new" id="addnewsubmit"><i class="fa fa-plus-circle"></i> ' . Language::_("tastycpanel.submit", true) . '</button>
</div>
<script type="text/javascript">
    $(document).ready(function() {
        $("#addnewsubmit").click(function () {
    var form = $("#addform").serialize();
    doAjaxPost("' . $this->base_uri . "services/manage/" . $service->id . "/email/addnew/?" . '"+ form, form);
        });
        $("#generate").click(function () {
            doAjaxGet("' . $this->base_uri . "services/manage/" . $service->id . "/changepassword/generatepass/" . '", "' . Language::_("tastycpanel.generated_password", true) . '");
        });
    });
</script>


    ';
                $this->Form->end();
            }

            exit();
        }
    }

    public function emailforwardersaddnew($package, $service, array $get = null, array $post = null, array $files = null) {

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            Loader::loadHelpers($this, array("Form", "Html"));
            $service_fields = $this->serviceFieldsToObject($service->fields);
            $module_row = $this->getModuleRow($package->module_row);
            $cpanel = $this->getcPanelApi($module_row->meta->accesskey, $module_row->meta->hostname, $module_row->meta->username, $module_row->meta->use_ssl, false);
            if (isset($post) && !empty($post)) {
                if ($post['domain'] !== "" && $post['email'] !== "" && $post['fwdopt'] !== "") {
                    if ($post['fwdopt'] === "fwd") {
                        $post['fwdemail'] = $post['text'];
                    } else if ($post['fwdopt'] === "system") {
                        $post['fwdsystem'] = $post['text'];
                    } else if ($post['fwdopt'] === "fail") {
                        $post['failmsgs'] = $post['text'];
                    } else if ($post['fwdopt'] === "pipe") {
                        $post['pipefwd'] = $post['text'];
                    }
                    Loader::loadModels($this, array("Services"));
                    $add_new = $cpanel->api2_query($service_fields->username, "Email", "addforward", $post);
                    $this->log($module_row->meta->hostname . "|Add New Email Forwarder", serialize("addforward"), "input", true);
                    if (isset($add_new->cpanelresult->data[0]->result) && $add_new->cpanelresult->data[0]->result == 0) {
                        $error = array(
                            0 => array(
                                "result" => $add_new->cpanelresult->data[0]->reason
                            )
                        );
                        echo "<div class='alert alert-danger alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>{$error[0]['result']}</p>
			</div>";
                    } else {
                        echo "<div class='alert alert-success alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>" . Language::_("tastycpanel.success", true) . "</p>
			</div><script>$(document).ready(function (e) { $('#global_modal').modal('hide');});</script>";
                    }
                } else {
                    $error = array(
                        0 => array(
                            "result" => Language::_("tastycpanel.empty_invalid_values", true)
                        )
                    );
                    echo "<div class='alert alert-danger alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>{$error[0]['result']}</p>
			</div>";
                }
            } else {
                $customfiles = $this->customStyleJS();
                $get_domains = $cpanel->api2_query($service_fields->username, "Email", "listmaildomains", array());
                $select_option = "<select name='domain' class='form-control' id='domain'>";
                foreach ($get_domains->cpanelresult->data as $key => $value) {
                    $select_option .= "<option value='{$get_domains->cpanelresult->data[$key]->domain}'>{$get_domains->cpanelresult->data[$key]->domain}</option>";
                }
                $select_option .= "</select>";

                $this->Form->create("", array('onsubmit' => 'return false', 'id' => 'addform', 'autocomplete' => "off"));
                echo" {$customfiles}
            <div class='modal-body'>
<div class='div_response'></div>";

                echo'<div class="form-group">
   <label>' . Language::_("tastycpanel.email.email_address", true) . '</label>
    <input type="email" class="form-control" value="" id="email" name="email" placeholder=""></div>
    <div class="form-group">
   <label>' . Language::_("tastycpanel.email.domain", true) . '</label>
   ' . $select_option . '</div>
  <div class="form-group"> <label>' . Language::_("tastycpanel.email.destination", true) . '</label>
<select name="fwdopt" class="form-control" id="fwdopt">
<option value="fwd">Forward to Email Address</option>
<option value="fail">Discard and send an error to the sender (at SMTP time)</option>
<option value="system">Forward to a system account</option>
<option value="pipe">Pipe to a Program</option>
<option value="blackhole">Discard (Not Recommended)</option>
 </select>
 </div>
<div class="form-group"><label>' . Language::_("tastycpanel.email.destination_desc", true) . '</label>
    <input type="text" class="form-control" value="" id="text" name="text" placeholder=""></div>
<div class="new_div"></div>
</div>
<div class="modal-footer">
<button type="button" name="cancel" class="btn btn-default" data-dismiss="modal"><i class="fa fa-ban"></i> ' . Language::_("tastycpanel.cancel", true) . '</button>
<button type="button" class="btn btn-primary" name="add_new" id="addnewsubmit"><i class="fa fa-plus-circle"></i> ' . Language::_("tastycpanel.submit", true) . '</button>
</div>
<script type="text/javascript">
    $(document).ready(function() {
        $("#addnewsubmit").click(function () {
    var form = $("#addform").serialize();
    doAjaxPost("' . $this->base_uri . "services/manage/" . $service->id . "/emailforwarders/addnew/?" . '"+ form, form);
        });
    });
</script>


    ';
                $this->Form->end();
            }

            exit();
        }
    }

    public function emailforwardersmain($package, $service, array $get = null, array $post = null, array $files = null) {
        $customfiles = $this->customStyleJS();
        $this->view = new View("email_forwarders", "default");
        $this->view->base_uri = $this->base_uri;
        Loader::loadHelpers($this, array("Form", "Html"));
        $service_fields = $this->serviceFieldsToObject($service->fields);
        $module_row = $this->getModuleRow($package->module_row);
        $cpanel = $this->getcPanelApi($module_row->meta->accesskey, $module_row->meta->hostname, $module_row->meta->username, $module_row->meta->use_ssl, false);

        if (isset($post['delete_emailforwarder'])) {
            if (!empty($post['email']) && !empty($post['emaildest'])) {
                $delete_email = $cpanel->api2_query($service_fields->username, "Email", "delforward", $post);
                $this->log($module_row->meta->hostname . "|Delete Email Forwarder", serialize("delforward"), "input", true);
                if (isset($delete_email->cpanelresult->data[0]->status) && $delete_email->cpanelresult->data[0]->status == 0) {
                    $error = array(
                        0 => array(
                            "result" => $delete_email->cpanelresult->data[0]->statusmsg
                        )
                    );
                    $this->Input->setErrors($error[0]['result']);
                }
            } else {
                $error = array(
                    0 => array(
                        "result" => Language::_("tastycpanel.empty_invalid_values", true)
                    )
                );
                $this->Input->setErrors($error[0]['result']);
            }
        } else if (isset($post['delete_domainforwarder'])) {
            if (!empty($post['domain'])) {
                $delete_email = $cpanel->api1_query($service_fields->username, "Email", "deldforward", $post);
                $this->log($module_row->meta->hostname . "|Delete Email Domain Forwarder", serialize("deldforward"), "input", true);
                if (isset($delete_email->cpanelresult->data[0]->status) && $delete_email->cpanelresult->data[0]->status == 0) {
                    $error = array(
                        0 => array(
                            "result" => $delete_email->cpanelresult->data[0]->statusmsg
                        )
                    );
                    $this->Input->setErrors($error[0]);
                }
            } else {
                $error = array(
                    0 => array(
                        "result" => Language::_("tastycpanel.empty_invalid_values", true)
                    )
                );
                $this->Input->setErrors($error[0]);
            }
        }




        $emailf_list = $cpanel->api2_query($service_fields->username, "Email", "listforwards", array());
        $emaild_list = $cpanel->api2_query($service_fields->username, "Email", "listdomainforwards", array());
        $this->view->set("module_row", $module_row);
        $this->view->set("service_fields", $service_fields);
        $this->view->set("emailf_list", $emailf_list->cpanelresult->data);
        $this->view->set("emaild_list", $emaild_list->cpanelresult->data);
        $this->view->set("type", $package->meta->type);
        $this->view->set("service_id", $service->id);



        $this->view->setDefaultView("components" . DS . "modules" . DS . "tastycpanelmodule" . DS);
        return $customfiles . $this->view->fetch();
    }

    public function emaildomainforwardersaddnew($package, $service, array $get = null, array $post = null, array $files = null) {

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            Loader::loadHelpers($this, array("Form", "Html"));
            $service_fields = $this->serviceFieldsToObject($service->fields);
            $module_row = $this->getModuleRow($package->module_row);
            $cpanel = $this->getcPanelApi($module_row->meta->accesskey, $module_row->meta->hostname, $module_row->meta->username, $module_row->meta->use_ssl, false);
            if (isset($post) && !empty($post)) {
                if ($post['domain'] !== "" && $post['destdomain'] !== "") {
                    Loader::loadModels($this, array("Services"));
                    $add_new = $cpanel->api2_query($service_fields->username, "Email", "adddomainforward", $post);
                    $this->log($module_row->meta->hostname . "|Add New Email Domain Forwarder", serialize("adddomainforward"), "input", true);
                    if (isset($add_new->cpanelresult->data[0]->status) && $add_new->cpanelresult->data[0]->status == 0) {
                        $error = array(
                            0 => array(
                                "result" => $add_new->cpanelresult->data[0]->statusmsg
                            )
                        );
                        echo "<div class='alert alert-danger alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>{$error[0]['result']}</p>
			</div>";
                    } else {
                        echo "<div class='alert alert-success alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>" . Language::_("tastycpanel.success", true) . "</p>
			</div><script>$(document).ready(function (e) { $('#global_modal').modal('hide');});</script>";
                    }
                } else {
                    $error = array(
                        0 => array(
                            "result" => Language::_("tastycpanel.empty_invalid_values", true)
                        )
                    );
                    echo "<div class='alert alert-danger alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>{$error[0]['result']}</p>
			</div>";
                }
            } else {
                $customfiles = $this->customStyleJS();
                $get_domains = $cpanel->api2_query($service_fields->username, "Email", "listmaildomains", array());
                $select_option = "<select name='domain' class='form-control' id='domain'>";
                foreach ($get_domains->cpanelresult->data as $key => $value) {
                    $select_option .= "<option value='{$get_domains->cpanelresult->data[$key]->domain}'>{$get_domains->cpanelresult->data[$key]->domain}</option>";
                }
                $select_option .= "</select>";

                $this->Form->create("", array('onsubmit' => 'return false', 'id' => 'addform', 'autocomplete' => "off"));
                echo" {$customfiles}
            <div class='modal-body'>
<div class='div_response'></div>";

                echo'
    <div class="form-group">
   <label>' . Language::_("tastycpanel.email.domain", true) . '</label>
   ' . $select_option . '</div>
<div class="form-group"><label>' . Language::_("tastycpanel.email.destination", true) . '</label>
    <input type="text" class="form-control" value="" id="destdomain" name="destdomain" placeholder=""></div>
<div class="new_div"></div>
</div>
<div class="modal-footer">
<button type="button" name="cancel" class="btn btn-default" data-dismiss="modal"><i class="fa fa-ban"></i> ' . Language::_("tastycpanel.cancel", true) . '</button>
<button type="button" class="btn btn-primary" name="add_new" id="addnewsubmit"><i class="fa fa-plus-circle"></i> ' . Language::_("tastycpanel.submit", true) . '</button>
</div>
<script type="text/javascript">
    $(document).ready(function() {
        $("#addnewsubmit").click(function () {
    var form = $("#addform").serialize();
    doAjaxPost("' . $this->base_uri . "services/manage/" . $service->id . "/emailforwarders/addnewdomainforward/?" . '"+ form, form);
        });
    });
</script>


    ';
                $this->Form->end();
            }

            exit();
        }
    }

    public function ftpmain($package, $service, array $get = null, array $post = null, array $files = null) {
        $customfiles = $this->customStyleJS();
        $this->view = new View("ftp_accounts", "default");
        $this->view->base_uri = $this->base_uri;
        Loader::loadHelpers($this, array("Form", "Html"));
        $service_fields = $this->serviceFieldsToObject($service->fields);
        $module_row = $this->getModuleRow($package->module_row);
        $cpanel = $this->getcPanelApi($module_row->meta->accesskey, $module_row->meta->hostname, $module_row->meta->username, $module_row->meta->use_ssl, false);

        if (isset($post['delete_ftp'])) {
            if (!empty($post['user'])) {
                $post['destroy'] = 1;
                $delete_email = $cpanel->api1_query($service_fields->username, "Ftp", "delftp", $post);
                $this->log($module_row->meta->hostname . "|Delete FTP Account", serialize("delftp"), "input", true);
                if (isset($delete_email->result) && $delete_email->result == 0) {
                    $error = array(
                        0 => array(
                            "result" => $delete_email->reason
                        )
                    );
                    $this->Input->setErrors($error[0]);
                }
            } else {
                $error = array(
                    0 => array(
                        "result" => Language::_("tastycpanel.empty_invalid_values", true)
                    )
                );
                $this->Input->setErrors($error[0]);
            }
        }





        $ftp_list = $cpanel->api2_query($service_fields->username, "Ftp", "listftpwithdisk", array("include_acct_types" => "sub"));
        $this->view->set("module_row", $module_row);
        $this->view->set("service_fields", $service_fields);
        $this->view->set("ftp_list", $this->addMBs($ftp_list)->cpanelresult->data);
        $this->view->set("type", $package->meta->type);
        $this->view->set("service_id", $service->id);



        $this->view->setDefaultView("components" . DS . "modules" . DS . "tastycpanelmodule" . DS);
        return $customfiles . $this->view->fetch();
    }

    public function ftpchangequota($package, $service, array $get = null, array $post = null, array $files = null) {

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            if (isset($get["user"])) {
                Loader::loadHelpers($this, array("Form", "Html"));
                $service_fields = $this->serviceFieldsToObject($service->fields);
                $module_row = $this->getModuleRow($package->module_row);
                $cpanel = $this->getcPanelApi($module_row->meta->accesskey, $module_row->meta->hostname, $module_row->meta->username, $module_row->meta->use_ssl, false);
                if (isset($post) && !empty($post)) {
                    if ($post['quota'] !== "" && $post['user'] !== "") {
                        Loader::loadModels($this, array("Services"));
                        $change_quota = $cpanel->api1_query($service_fields->username, "Ftp", "ftpquota", $post);
                        $this->log($module_row->meta->hostname . "|Change FTP Account Quota", serialize("ftpquota"), "input", true);
                        if (isset($change_quota->cpanelresult->error) && !empty($change_quota->cpanelresult->error)) {
                            $error = array(
                                0 => array(
                                    "result" => $change_quota->cpanelresult->error
                                )
                            );
                            echo "<div class='alert alert-danger alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>{$error[0]['result']}</p>
			</div>";
                        } else {
                            echo "<div class='alert alert-success alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>" . Language::_("tastycpanel.success", true) . "</p>
			</div><script>$(document).ready(function (e) { $('#global_modal').modal('hide');});</script>";
                        }
                    } else {
                        $error = array(
                            0 => array(
                                "result" => Language::_("tastycpanel.empty_invalid_values", true)
                            )
                        );
                        echo "<div class='alert alert-danger alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>{$error[0]['result']}</p>
			</div>";
                    }
                } else {
                    $customfiles = $this->customStyleJS();

                    $this->Form->create("", array('onsubmit' => 'return false', 'id' => 'changequotaform', 'autocomplete' => "off"));
                    echo" {$customfiles}
            <div class='modal-body'>
            <div class='div_response'></div>";
                    $this->Form->fieldHidden("user", $this->Html->ifSet($get["user"]), array('id' => "user"));

                    echo'<div class="form-group">
   <label>' . Language::_("tastycpanel.ftp.changequota", true) . " " . Language::_("tastycpanel.for", true) . " " . $get["user"] . '@' . $service_fields->domain_name . ' </label>
    <input type="text" class="form-control" id="quota" name="quota" placeholder="e.g: 250 OR Unlimited">
</div>
</div>
<div class="modal-footer">
<button type="button" name="cancel" class="btn btn-default" data-dismiss="modal"><i class="fa fa-ban"></i> ' . Language::_("tastycpanel.cancel", true) . '</button>
<button type="button" class="btn btn-primary" name="change_quota" id="change_quota"><i class="fa fa-edit"></i> ' . Language::_("tastycpanel.submit", true) . '</button>
</div>
<script type="text/javascript">
    $(document).ready(function () {
        $("#change_quota").click(function () {
    var form = $("#changequotaform").serialize();
   doAjaxPost("' . $this->base_uri . "services/manage/" . $service->id . "/ftpaccounts/changequota/?" . '"+ form, form);
        });
    });
</script>


    ';
                    $this->Form->end();
                }

                exit();
            } else {
                return false;
            }
        }
    }

    public function ftpchangepassword($package, $service, array $get = null, array $post = null, array $files = null) {

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            if (isset($get["user"])) {
                Loader::loadHelpers($this, array("Form", "Html"));
                $service_fields = $this->serviceFieldsToObject($service->fields);
                $module_row = $this->getModuleRow($package->module_row);
                $cpanel = $this->getcPanelApi($module_row->meta->accesskey, $module_row->meta->hostname, $module_row->meta->username, $module_row->meta->use_ssl, false);
                if (isset($post) && !empty($post)) {
                    if ($post['user'] !== "" && $post['pass'] !== "") {
                        Loader::loadModels($this, array("Services"));
                        $change_password = $cpanel->api1_query($service_fields->username, "Ftp", "passwdftp", $post);
                        $this->log($module_row->meta->hostname . "|Change FTP Password", serialize("passwdftp"), "input", true);
                        if (isset($change_password->cpanelresult->error) && !empty($change_password->cpanelresult->error)) {
                            $error = array(
                                0 => array(
                                    "result" => $change_password->cpanelresult->error
                                )
                            );
                            echo "<div class='alert alert-danger alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>{$error[0]['result']}</p>
			</div>";
                        } else {
                            echo "<div class='alert alert-success alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>" . Language::_("tastycpanel.success", true) . "</p>
			</div><script>$(document).ready(function (e) { $('#global_modal').modal('hide');});</script>";
                        }
                    } else {
                        $error = array(
                            0 => array(
                                "result" => Language::_("tastycpanel.empty_invalid_values", true)
                            )
                        );
                        echo "<div class='alert alert-danger alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>{$error[0]['result']}</p>
			</div>";
                    }
                } else {
                    $customfiles = $this->customStyleJS();

                    $this->Form->create("", array('onsubmit' => 'return false', 'id' => 'changepassform', 'autocomplete' => "off"));
                    echo" {$customfiles}
            <div class='modal-body'>
<div class='div_response'></div>";
                    $this->Form->fieldHidden("user", $this->Html->ifSet($get["user"]), array('id' => "user"));

                    echo'<div class="form-group">
   <label>' . Language::_("tastycpanel.changepassword", true) . " " . Language::_("tastycpanel.for", true) . " " . $get["user"] . '@' . $service_fields->domain_name . ' </label>
    <input type="password" class="form-control" value="" id="pass" name="pass" placeholder="**********">
</div>
<div class="new_div"></div>
</div>
<div class="modal-footer">
<button type="button" name="cancel" class="btn btn-default" data-dismiss="modal"><i class="fa fa-ban"></i> ' . Language::_("tastycpanel.cancel", true) . '</button>
<button type="button" class="btn btn-default" id="generate"><i class="fa fa-key"></i> ' . Language::_("tastycpanel.service.generate", true) . '</button>
<button type="button" class="btn btn-primary" name="change_password" id="change_password"><i class="fa fa-edit"></i> ' . Language::_("tastycpanel.submit", true) . '</button>
</div>
<script type="text/javascript">
    $(document).ready(function () {
        $("#change_password").click(function () {
    var form = $("#changepassform").serialize();
    doAjaxPost("' . $this->base_uri . "services/manage/" . $service->id . "/ftpaccounts/changepassword/?" . '"+ form, form);
        });
        $("#generate").click(function () {
            doAjaxGet("' . $this->base_uri . "services/manage/" . $service->id . "/changepassword/generatepass/" . '", "' . Language::_("tastycpanel.generated_password", true) . '");
        });
    });
</script>


    ';
                    $this->Form->end();
                }

                exit();
            } else {
                return false;
            }
        }
    }

    public function ftpaddnew($package, $service, array $get = null, array $post = null, array $files = null) {

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            Loader::loadHelpers($this, array("Form", "Html"));
            $service_fields = $this->serviceFieldsToObject($service->fields);
            $module_row = $this->getModuleRow($package->module_row);
            $cpanel = $this->getcPanelApi($module_row->meta->accesskey, $module_row->meta->hostname, $module_row->meta->username, $module_row->meta->use_ssl, false);
            if (isset($post) && !empty($post)) {
                if ($post['user'] !== "" && $post['pass'] !== "" && $post['quota'] !== "" && $post['homedir'] !== "") {
                    if ($post['homedir'] === "public_html/") {
                        $post['homedir'] = "public_html";
                    }
                    $post['disallowdot'] = 1;
                    Loader::loadModels($this, array("Services"));
                    $add_new = $cpanel->api1_query($service_fields->username, "Ftp", "addftp", $post);
                    $this->log($module_row->meta->hostname . "|Add New FTP", serialize("addftp"), "input", true);
                    if (isset($add_new->error) && !empty($add_new->error)) {
                        $error = array(
                            0 => array(
                                "result" => $add_new->error
                            )
                        );
                        echo "<div class='alert alert-danger alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>{$error[0]['result']}</p>
			</div>";
                    } else {
                        echo "<div class='alert alert-success alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>" . Language::_("tastycpanel.success", true) . "</p>
			</div><script>$(document).ready(function (e) { $('#global_modal').modal('hide');});</script>";
                    }
                } else {
                    $error = array(
                        0 => array(
                            "result" => Language::_("tastycpanel.empty_invalid_values", true)
                        )
                    );
                    echo "<div class='alert alert-danger alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>{$error[0]['result']}</p>
			</div>";
                }
            } else {
                $customfiles = $this->customStyleJS();

                $this->Form->create("", array('onsubmit' => 'return false', 'id' => 'addform', 'autocomplete' => "off"));
                echo" {$customfiles}
            <div class='modal-body'>
<div class='div_response'></div>";

                echo'<div class="form-group">
   <label>' . Language::_("tastycpanel.ftp.user", true) . '</label>
<div class="input-group">
  <input type="text" class="form-control" name="user" placeholder="user" aria-describedby="user_s">
  <span class="input-group-addon" id="user_s">@' . $service_fields->domain_name . '</span>
</div>
</div>
   <div class="form-group"><label>' . Language::_("tastycpanel.password", true) . '</label>
    <input type="password" class="form-control" value="" id="pass" name="pass" placeholder="***********"></div>
<div class="new_div"></div>
  <div class="form-group"> <label>' . Language::_("tastycpanel.ftp.homedir", true) . '</label>
<div class="input-group">
  <span class="input-group-addon" id="homedir_s">/home/' . $service_fields->username . '</span>
  <input type="text" class="form-control" name="homedir" placeholder="e.g: public_html/testaccount" aria-describedby="homedir_s">
</div>
</div>

<div class="form-group"> <label>' . Language::_("tastycpanel.ftp.quota", true) . '</label>
    <input type="text" class="form-control" value="" id="quota" name="quota" placeholder="e.g: 250 OR Unlimited"></div>
</div>
</div>
<div class="modal-footer">
<button type="button" name="cancel" class="btn btn-default" data-dismiss="modal"><i class="fa fa-ban"></i> ' . Language::_("tastycpanel.cancel", true) . '</button>
<button type="button" class="btn btn-default" id="generate"><i class="fa fa-key"></i> ' . Language::_("tastycpanel.service.generate", true) . '</button>
<button type="button" class="btn btn-primary" name="add_new" id="addnewsubmit"><i class="fa fa-plus-circle"></i> ' . Language::_("tastycpanel.submit", true) . '</button>
</div>
<script type="text/javascript">
    $(document).ready(function() {
        $("#addnewsubmit").click(function () {
    var form = $("#addform").serialize();
    doAjaxPost("' . $this->base_uri . "services/manage/" . $service->id . "/ftpaccounts/addnew/?" . '"+ form, form);
        });
        $("#generate").click(function () {
            doAjaxGet("' . $this->base_uri . "services/manage/" . $service->id . "/changepassword/generatepass/" . '", "' . Language::_("tastycpanel.generated_password", true) . '");
        });
    });
</script>


    ';
                $this->Form->end();
            }

            exit();
        }
    }

    public function subdomainsmain($package, $service, array $get = null, array $post = null, array $files = null) {
        $customfiles = $this->customStyleJS();
        $this->view = new View("sub_domains", "default");
        $this->view->base_uri = $this->base_uri;
        Loader::loadHelpers($this, array("Form", "Html"));
        $service_fields = $this->serviceFieldsToObject($service->fields);
        $module_row = $this->getModuleRow($package->module_row);
        $cpanel = $this->getcPanelApi($module_row->meta->accesskey, $module_row->meta->hostname, $module_row->meta->username, $module_row->meta->use_ssl, false);

        if (isset($post['delete_subdomain'])) {
            if (!empty($post['domain'])) {
                $delete_email = $cpanel->api2_query($service_fields->username, "SubDomain", "delsubdomain", $post);
                $this->log($module_row->meta->hostname . "|Delete Sub Domain", serialize("delsubdomain"), "input", true);
                if (isset($delete_email->cpanelresult->data[0]->result) && $delete_email->cpanelresult->data[0]->result == 0) {
                    $error = array(
                        0 => array(
                            "result" => $delete_email->cpanelresult->data[0]->reason
                        )
                    );
                    $this->Input->setErrors($error[0]);
                }
            } else {
                $error = array(
                    0 => array(
                        "result" => Language::_("tastycpanel.empty_invalid_values", true)
                    )
                );
                $this->Input->setErrors($error[0]);
            }
        }





        $subdomains_list = $cpanel->api2_query($service_fields->username, "SubDomain", "listsubdomains", array());
        $this->view->set("module_row", $module_row);
        $this->view->set("service_fields", $service_fields);
        $this->view->set("subdomains_list", $subdomains_list->cpanelresult->data);
        $this->view->set("type", $package->meta->type);
        $this->view->set("service_id", $service->id);



        $this->view->setDefaultView("components" . DS . "modules" . DS . "tastycpanelmodule" . DS);
        return $customfiles . $this->view->fetch();
    }

    public function subdomainsaddnew($package, $service, array $get = null, array $post = null, array $files = null) {

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            Loader::loadHelpers($this, array("Form", "Html"));
            $service_fields = $this->serviceFieldsToObject($service->fields);
            $module_row = $this->getModuleRow($package->module_row);
            $cpanel = $this->getcPanelApi($module_row->meta->accesskey, $module_row->meta->hostname, $module_row->meta->username, $module_row->meta->use_ssl, false);
            if (isset($post) && !empty($post)) {
                if ($post['domain'] !== "" && $post['rootdomain'] !== "") {
                    $post['disallowdot'] = 1;
                    Loader::loadModels($this, array("Services"));
                    $add_new = $cpanel->api2_query($service_fields->username, "SubDomain", "addsubdomain", $post);
                    $this->log($module_row->meta->hostname . "|Add New Sub Domain", serialize("addsubdomain"), "input", true);
                    if (isset($add_new->cpanelresult->data[0]->result) && $add_new->cpanelresult->data[0]->result == 0) {
                        $error = array(
                            0 => array(
                                "result" => $add_new->cpanelresult->data[0]->reason
                            )
                        );
                        echo "<div class='alert alert-danger alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>{$error[0]['result']}</p>
			</div>";
                    } else {
                        echo "<div class='alert alert-success alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>" . Language::_("tastycpanel.success", true) . "</p>
			</div><script>$(document).ready(function (e) { $('#global_modal').modal('hide');});</script>";
                    }
                } else {
                    $error = array(
                        0 => array(
                            "result" => Language::_("tastycpanel.empty_invalid_values", true)
                        )
                    );
                    echo "<div class='alert alert-danger alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>{$error[0]['result']}</p>
			</div>";
                }
            } else {
                $customfiles = $this->customStyleJS();
                $get_domains = $cpanel->api2_query($service_fields->username, "Email", "listmaildomains", array());
                $select_option = "<select name='rootdomain' class='form-control' id='rootdomain'>";
                foreach ($get_domains->cpanelresult->data as $key => $value) {
                    $select_option .= "<option value='{$get_domains->cpanelresult->data[$key]->domain}'>{$get_domains->cpanelresult->data[$key]->domain}</option>";
                }
                $select_option .= "</select>";

                $this->Form->create("", array('onsubmit' => 'return false', 'id' => 'addform', 'autocomplete' => "off"));
                echo" {$customfiles}
            <div class='modal-body'>
<div class='div_response'></div>";

                echo'<div class="form-group">
   <label>' . Language::_("tastycpanel.subdomain", true) . '</label>
    <input type="text" class="form-control" value="" id="domain" name="domain" placeholder=""></div>
    <div class="form-group">
   <label>' . Language::_("tastycpanel.email.domain", true) . '</label>
   ' . $select_option . '</div>
  <div class="form-group"> <label>' . Language::_("tastycpanel.ftp.homedir", true) . '</label>
<div class="input-group">
  <span class="input-group-addon" id="dir_s">/home/' . $service_fields->username . '</span>
  <input type="text" class="form-control" name="dir" placeholder="e.g: public_html/testdomain" aria-describedby="dir_s">
</div>
  <span class="label label-info module" style="width:100%">' . Language::_("tastycpanel.subdomain.leaveitemptydir", true) . '</span>
</div>
</div>
<div class="modal-footer">
<button type="button" name="cancel" class="btn btn-default" data-dismiss="modal"><i class="fa fa-ban"></i> ' . Language::_("tastycpanel.cancel", true) . '</button>
<button type="button" class="btn btn-primary" name="add_new" id="addnewsubmit"><i class="fa fa-plus-circle"></i> ' . Language::_("tastycpanel.submit", true) . '</button>
</div>
<script type="text/javascript">
    $(document).ready(function() {
        $("#addnewsubmit").click(function () {
    var form = $("#addform").serialize();
    doAjaxPost("' . $this->base_uri . "services/manage/" . $service->id . "/subdomains/addnew/?" . '"+ form, form);
        });
    });
</script>


    ';
                $this->Form->end();
            }

            exit();
        }
    }

    public function addondomainsmain($package, $service, array $get = null, array $post = null, array $files = null) {
        $customfiles = $this->customStyleJS();
        $this->view = new View("addon_domains", "default");
        $this->view->base_uri = $this->base_uri;
        Loader::loadHelpers($this, array("Form", "Html"));
        $service_fields = $this->serviceFieldsToObject($service->fields);
        $module_row = $this->getModuleRow($package->module_row);
        $cpanel = $this->getcPanelApi($module_row->meta->accesskey, $module_row->meta->hostname, $module_row->meta->username, $module_row->meta->use_ssl, false);

        if (isset($post['delete_addondomain'])) {
            if (!empty($post['domain']) && !empty($post['subdomain'])) {
                $delete_email = $cpanel->api2_query($service_fields->username, "AddonDomain", "deladdondomain", $post);
                $this->log($module_row->meta->hostname . "|Delete Addon Domain", serialize("deladdondomain"), "input", true);
                if (isset($delete_email->cpanelresult->data[0]->result) && $delete_email->cpanelresult->data[0]->result == 0) {
                    $error = array(
                        0 => array(
                            "result" => $delete_email->cpanelresult->data[0]->reason
                        )
                    );
                    $this->Input->setErrors($error[0]);
                }
            } else {
                $error = array(
                    0 => array(
                        "result" => Language::_("tastycpanel.empty_invalid_values", true)
                    )
                );
                $this->Input->setErrors($error[0]);
            }
        }





        $addondomains_list = $cpanel->api2_query($service_fields->username, "AddonDomain", "listaddondomains", array());
        $this->view->set("module_row", $module_row);
        $this->view->set("service_fields", $service_fields);
        $this->view->set("addondomains_list", $addondomains_list->cpanelresult->data);
        $this->view->set("type", $package->meta->type);
        $this->view->set("service_id", $service->id);



        $this->view->setDefaultView("components" . DS . "modules" . DS . "tastycpanelmodule" . DS);
        return $customfiles . $this->view->fetch();
    }

    public function addondomainsaddnew($package, $service, array $get = null, array $post = null, array $files = null) {

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            Loader::loadHelpers($this, array("Form", "Html"));
            $service_fields = $this->serviceFieldsToObject($service->fields);
            $module_row = $this->getModuleRow($package->module_row);
            $cpanel = $this->getcPanelApi($module_row->meta->accesskey, $module_row->meta->hostname, $module_row->meta->username, $module_row->meta->use_ssl, false);
            if (isset($post) && !empty($post)) {
                if ($post['newdomain'] !== "" && $post['subdomain'] !== "") {
                    Loader::loadModels($this, array("Services"));
                    $add_new = $cpanel->api2_query($service_fields->username, "AddonDomain", "addaddondomain", $post);
                    $this->log($module_row->meta->hostname . "|Add New Addon Domain", serialize("addaddondomain"), "input", true);
                    if (isset($add_new->cpanelresult->data[0]->result) && $add_new->cpanelresult->data[0]->result == 0) {
                        $error = array(
                            0 => array(
                                "result" => $add_new->cpanelresult->data[0]->reason
                            )
                        );
                        echo "<div class='alert alert-danger alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>{$error[0]['result']}</p>
			</div>";
                    } else {
                        echo "<div class='alert alert-success alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>" . Language::_("tastycpanel.success", true) . "</p>
			</div><script>$(document).ready(function (e) { $('#global_modal').modal('hide');});</script>";
                    }
                } else {
                    $error = array(
                        0 => array(
                            "result" => Language::_("tastycpanel.empty_invalid_values", true)
                        )
                    );
                    echo "<div class='alert alert-danger alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>{$error[0]['result']}</p>
			</div>";
                }
            } else {
                $customfiles = $this->customStyleJS();

                $this->Form->create("", array('onsubmit' => 'return false', 'id' => 'addform', 'autocomplete' => "off"));
                echo" {$customfiles}
            <div class='modal-body'>
<div class='div_response'></div>";

                echo'<div class="form-group">
   <label>' . Language::_("tastycpanel.addondomain", true) . '</label>
    <input type="text" class="form-control" value="" id="newdomain" name="newdomain" placeholder="e.g: domainname.com"></div>
    <div class="form-group">
   <label>' . Language::_("tastycpanel.subdomain", true) . '</label>
    <input type="text" class="form-control" value="" id="subdomain" name="subdomain" placeholder="e.g: domainname"></div>
  <div class="form-group"> <label>' . Language::_("tastycpanel.addondomain.documentroot", true) . '</label>
<div class="input-group">
  <span class="input-group-addon" id="dir_s">/home/' . $service_fields->username . '</span>
  <input type="text" class="form-control" name="dir" placeholder="e.g: addondomain/home/dir" aria-describedby="dir_s">
</div>
  <span class="label label-info module" style="width:100%">' . Language::_("tastycpanel.subdomain.leaveitemptydir", true) . '</span>
</div>
</div>
<div class="modal-footer">
<button type="button" name="cancel" class="btn btn-default" data-dismiss="modal"><i class="fa fa-ban"></i> ' . Language::_("tastycpanel.cancel", true) . '</button>
<button type="button" class="btn btn-primary" name="add_new" id="addnewsubmit"><i class="fa fa-plus-circle"></i> ' . Language::_("tastycpanel.submit", true) . '</button>
</div>
<script type="text/javascript">
    $(document).ready(function() {
        $("#addnewsubmit").click(function () {
    var form = $("#addform").serialize();
    doAjaxPost("' . $this->base_uri . "services/manage/" . $service->id . "/addondomains/addnew/?" . '"+ form, form);
        });
    });
</script>


    ';
                $this->Form->end();
            }

            exit();
        }
    }

    public function parkeddomainsmain($package, $service, array $get = null, array $post = null, array $files = null) {
        $customfiles = $this->customStyleJS();
        $this->view = new View("parked_domains", "default");
        $this->view->base_uri = $this->base_uri;
        Loader::loadHelpers($this, array("Form", "Html"));
        $service_fields = $this->serviceFieldsToObject($service->fields);
        $module_row = $this->getModuleRow($package->module_row);
        $cpanel = $this->getcPanelApi($module_row->meta->accesskey, $module_row->meta->hostname, $module_row->meta->username, $module_row->meta->use_ssl, false);

        if (isset($post['delete_parkeddomain'])) {
            if (!empty($post['domain'])) {
                $delete_email = $cpanel->api2_query($service_fields->username, "Park", "unpark", $post);
                $this->log($module_row->meta->hostname . "|Delete Parked Domain", serialize("unpark"), "input", true);
                if (isset($delete_email->cpanelresult->data[0]->result) && $delete_email->cpanelresult->data[0]->result == 0) {
                    $error = array(
                        0 => array(
                            "result" => $delete_email->cpanelresult->data[0]->reason
                        )
                    );
                    $this->Input->setErrors($error[0]);
                }
            } else {
                $error = array(
                    0 => array(
                        "result" => Language::_("tastycpanel.empty_invalid_values", true)
                    )
                );
                $this->Input->setErrors($error[0]);
            }
        }





        $parkeddomains_list = $cpanel->api2_query($service_fields->username, "Park", "listparkeddomains", array());
        $this->view->set("module_row", $module_row);
        $this->view->set("service_fields", $service_fields);
        $this->view->set("parkeddomains_list", $parkeddomains_list->cpanelresult->data);
        $this->view->set("type", $package->meta->type);
        $this->view->set("service_id", $service->id);



        $this->view->setDefaultView("components" . DS . "modules" . DS . "tastycpanelmodule" . DS);
        return $customfiles . $this->view->fetch();
    }

    public function parkeddomainsaddnew($package, $service, array $get = null, array $post = null, array $files = null) {

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            Loader::loadHelpers($this, array("Form", "Html"));
            $service_fields = $this->serviceFieldsToObject($service->fields);
            $module_row = $this->getModuleRow($package->module_row);
            $cpanel = $this->getcPanelApi($module_row->meta->accesskey, $module_row->meta->hostname, $module_row->meta->username, $module_row->meta->use_ssl, false);
            if (isset($post) && !empty($post)) {
                if ($post['domain'] !== "") {
                    Loader::loadModels($this, array("Services"));
                    $add_new = $cpanel->api2_query($service_fields->username, "Park", "park", $post);
                    $this->log($module_row->meta->hostname . "|Add New Parked Domain", serialize("park"), "input", true);
                    if (isset($add_new->cpanelresult->data[0]->result) && $add_new->cpanelresult->data[0]->result == 0) {
                        $error = array(
                            0 => array(
                                "result" => $add_new->cpanelresult->data[0]->reason
                            )
                        );
                        echo "<div class='alert alert-danger alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>{$error[0]['result']}</p>
			</div>";
                    } else {
                        echo "<div class='alert alert-success alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>" . Language::_("tastycpanel.success", true) . "</p>
			</div><script>$(document).ready(function (e) { $('#global_modal').modal('hide');});</script>";
                    }
                } else {
                    $error = array(
                        0 => array(
                            "result" => Language::_("tastycpanel.empty_invalid_values", true)
                        )
                    );
                    echo "<div class='alert alert-danger alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>{$error[0]['result']}</p>
			</div>";
                }
            } else {
                $customfiles = $this->customStyleJS();

                $this->Form->create("", array('onsubmit' => 'return false', 'id' => 'addform', 'autocomplete' => "off"));
                echo" {$customfiles}
            <div class='modal-body'>
<div class='div_response'></div>";

                echo'<div class="form-group">
   <label>' . Language::_("tastycpanel.email.domain", true) . '</label>
    <input type="text" class="form-control" value="" id="domain" name="domain" placeholder="e.g: domainname.com"></div>
</div>
<div class="modal-footer">
<button type="button" name="cancel" class="btn btn-default" data-dismiss="modal"><i class="fa fa-ban"></i> ' . Language::_("tastycpanel.cancel", true) . '</button>
<button type="button" class="btn btn-primary" name="add_new" id="addnewsubmit"><i class="fa fa-plus-circle"></i> ' . Language::_("tastycpanel.submit", true) . '</button>
</div>
<script type="text/javascript">
    $(document).ready(function() {
        $("#addnewsubmit").click(function () {
    var form = $("#addform").serialize();
    doAjaxPost("' . $this->base_uri . "services/manage/" . $service->id . "/parkeddomains/addnew/?" . '"+ form, form);
        });
    });
</script>


    ';
                $this->Form->end();
            }

            exit();
        }
    }

    public function cronjobsmain($package, $service, array $get = null, array $post = null, array $files = null) {
        $customfiles = $this->customStyleJS();
        $this->view = new View("cron_jobs", "default");
        $this->view->base_uri = $this->base_uri;
        Loader::loadHelpers($this, array("Form", "Html"));
        $service_fields = $this->serviceFieldsToObject($service->fields);
        $module_row = $this->getModuleRow($package->module_row);
        $cpanel = $this->getcPanelApi($module_row->meta->accesskey, $module_row->meta->hostname, $module_row->meta->username, $module_row->meta->use_ssl, false);

        if (isset($post['delete_cronjob'])) {
            if (!empty($post['line'])) {
                $delete_email = $cpanel->api2_query($service_fields->username, "Cron", "remove_line", $post);
                $this->log($module_row->meta->hostname . "|Delete Cron Job", serialize("remove_line"), "input", true);
                if (isset($delete_email->cpanelresult->data[0]->status) && $delete_email->cpanelresult->data[0]->status == 0) {
                    $error = array(
                        0 => array(
                            "result" => $delete_email->cpanelresult->data[0]->statusmsg
                        )
                    );
                    $this->Input->setErrors($error[0]['result']);
                }
            } else {
                $error = array(
                    0 => array(
                        "result" => Language::_("tastycpanel.empty_invalid_values", true)
                    )
                );
                $this->Input->setErrors($error[0]['result']);
            }
        }


        $cronjobs_list = $cpanel->api2_query($service_fields->username, "Cron", "listcron", array());
        foreach ($cronjobs_list->cpanelresult->data as $key => $value) {
            if ((empty($cronjobs_list->cpanelresult->data[$key]->minute) && empty($cronjobs_list->cpanelresult->data[$key]->hour) && empty($cronjobs_list->cpanelresult->data[$key]->day) && empty($cronjobs_list->cpanelresult->data[$key]->month) && empty($cronjobs_list->cpanelresult->data[$key]->weekday)) || empty($cronjobs_list->cpanelresult->data[$key]->count)) {
                unset($cronjobs_list->cpanelresult->data[$key]);
            }
        }
        $this->view->set("module_row", $module_row);
        $this->view->set("service_fields", $service_fields);
        $this->view->set("cronjobs_list", $cronjobs_list->cpanelresult->data);
        $this->view->set("type", $package->meta->type);
        $this->view->set("service_id", $service->id);



        $this->view->setDefaultView("components" . DS . "modules" . DS . "tastycpanelmodule" . DS);
        return $customfiles . $this->view->fetch();
    }

    public function cronjobsaddnew($package, $service, array $get = null, array $post = null, array $files = null) {

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            Loader::loadHelpers($this, array("Form", "Html"));
            $service_fields = $this->serviceFieldsToObject($service->fields);
            $module_row = $this->getModuleRow($package->module_row);
            $cpanel = $this->getcPanelApi($module_row->meta->accesskey, $module_row->meta->hostname, $module_row->meta->username, $module_row->meta->use_ssl, false);
            if (isset($post) && !empty($post)) {
                if ($post['weekday'] !== "" && $post['minute'] !== "" && $post['hour'] !== "" && $post['day'] !== "" && $post['month'] !== "" && $post['command'] !== "") {
                    Loader::loadModels($this, array("Services"));
                    $add_new = $cpanel->api2_query($service_fields->username, "Cron", "add_line", $post);
                    $this->log($module_row->meta->hostname . "|Add New Cron Job Line", serialize("add_line"), "input", true);
                    if (isset($add_new->cpanelresult->data[0]->status) && $add_new->cpanelresult->data[0]->status == 0) {
                        $error = array(
                            0 => array(
                                "result" => $add_new->cpanelresult->data[0]->statusmsg
                            )
                        );
                        echo "<div class='alert alert-danger alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>{$error[0]['result']}</p>
			</div>";
                    } else {
                        echo "<div class='alert alert-success alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>" . Language::_("tastycpanel.success", true) . "</p>
			</div><script>$(document).ready(function (e) { $('#global_modal').modal('hide');});</script>";
                    }
                } else {
                    $error = array(
                        0 => array(
                            "result" => Language::_("tastycpanel.empty_invalid_values", true)
                        )
                    );
                    echo "<div class='alert alert-danger alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>{$error[0]['result']}</p>
			</div>";
                }
            } else {
                $customfiles = $this->customStyleJS();
                $min_opt = "";
                $hour_opt = "";
                $day_opt = "";
                $month_opt = "";
                $weekday_opt = "";
                $min_array = $this->cronSelectValues("min");
                $hour_array = $this->cronSelectValues("hour");
                $day_array = $this->cronSelectValues("day");
                $month_array = $this->cronSelectValues("month");
                $weekday_array = $this->cronSelectValues("weekday");
                foreach ($min_array as $key => $value) {
                    $min_opt .= "<option value='{$key}'>{$min_array[$key]}</option>";
                }
                foreach ($hour_array as $key => $value) {
                    $hour_opt .= "<option value='{$key}'>{$hour_array[$key]}</option>";
                }
                foreach ($day_array as $key => $value) {
                    $day_opt .= "<option value='{$key}'>{$day_array[$key]}</option>";
                }
                foreach ($month_array as $key => $value) {
                    $month_opt .= "<option value='{$key}'>{$month_array[$key]}</option>";
                }
                foreach ($weekday_array as $key => $value) {
                    $weekday_opt .= "<option value='{$key}'>{$weekday_array[$key]}</option>";
                }

                $this->Form->create("", array('onsubmit' => 'return false', 'id' => 'addform', 'autocomplete' => "off"));
                echo" {$customfiles}
            <div class='modal-body'>
<div class='div_response'></div>";

                echo'<div class="form-group">
   <label>' . Language::_("tastycpanel.cronjob.min", true) . '</label>
<select name="minute" class="form-control">
' . $min_opt . '
</select>
</div>
<div class="form-group">
   <label>' . Language::_("tastycpanel.cronjob.hour", true) . '</label>
<select name="hour" class="form-control">
' . $hour_opt . '
</select>
</div>
<div class="form-group">
   <label>' . Language::_("tastycpanel.cronjob.day", true) . '</label>
<select name="day" class="form-control">
' . $day_opt . '
</select>
</div>
<div class="form-group">
   <label>' . Language::_("tastycpanel.cronjob.month", true) . '</label>
<select name="month" class="form-control">
' . $month_opt . '
</select>
</div>
<div class="form-group">
   <label>' . Language::_("tastycpanel.cronjob.weekday", true) . '</label>
<select name="weekday" class="form-control">
' . $weekday_opt . '
</select>
</div>
<div class="form-group">
   <label>' . Language::_("tastycpanel.cronjob.command", true) . '</label>
    <input type="text" class="form-control" value="" id="command" name="command" placeholder=""></div>
</div>
<div class="modal-footer">
<button type="button" name="cancel" class="btn btn-default" data-dismiss="modal"><i class="fa fa-ban"></i> ' . Language::_("tastycpanel.cancel", true) . '</button>
<button type="button" class="btn btn-primary" name="add_new" id="addnewsubmit"><i class="fa fa-plus-circle"></i> ' . Language::_("tastycpanel.submit", true) . '</button>
</div>
<script type="text/javascript">
    $(document).ready(function() {
        $("#addnewsubmit").click(function () {
    var form = $("#addform").serialize();
    doAjaxPost("' . $this->base_uri . "services/manage/" . $service->id . "/cronjobs/addnew/?" . '"+ form, form);
        });
    });
</script>


    ';
                $this->Form->end();
            }

            exit();
        }
    }

    public function ipblockermain($package, $service, array $get = null, array $post = null, array $files = null) {
        $customfiles = $this->customStyleJS();
        $this->view = new View("ip_blocker", "default");
        $this->view->base_uri = $this->base_uri;
        Loader::loadHelpers($this, array("Form", "Html"));
        $service_fields = $this->serviceFieldsToObject($service->fields);
        $module_row = $this->getModuleRow($package->module_row);
        $cpanel = $this->getcPanelApi($module_row->meta->accesskey, $module_row->meta->hostname, $module_row->meta->username, $module_row->meta->use_ssl, false);

        if (isset($post['delete_denyip'])) {
            if (!empty($post['ip'])) {
                $delete_email = $cpanel->api1_query($service_fields->username, "DenyIp", "deldenyip", $post);
                $this->log($module_row->meta->hostname . "|Delete Denied IP", serialize("deldenyip"), "input", true);
            } else {
                $error = array(
                    0 => array(
                        "result" => Language::_("tastycpanel.empty_invalid_values", true)
                    )
                );
                $this->Input->setErrors($error[0]);
            }
        }





        $ipblocker_list = $cpanel->api2_query($service_fields->username, "DenyIp", "listdenyips", array());
        $this->view->set("module_row", $module_row);
        $this->view->set("service_fields", $service_fields);
        $this->view->set("ipblocker_list", $ipblocker_list->cpanelresult->data);
        $this->view->set("type", $package->meta->type);
        $this->view->set("service_id", $service->id);



        $this->view->setDefaultView("components" . DS . "modules" . DS . "tastycpanelmodule" . DS);
        return $customfiles . $this->view->fetch();
    }

    public function ipblockeraddnew($package, $service, array $get = null, array $post = null, array $files = null) {

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            Loader::loadHelpers($this, array("Form", "Html"));
            $service_fields = $this->serviceFieldsToObject($service->fields);
            $module_row = $this->getModuleRow($package->module_row);
            $cpanel = $this->getcPanelApi($module_row->meta->accesskey, $module_row->meta->hostname, $module_row->meta->username, $module_row->meta->use_ssl, false);
            if (isset($post) && !empty($post)) {
                if ($post['ip'] !== "") {
                    Loader::loadModels($this, array("Services"));
                    $add_new = $cpanel->api1_query($service_fields->username, "DenyIp", "adddenyip", $post);
                    $this->log($module_row->meta->hostname . "|Add New Denied IP", serialize("adddenyip"), "input", true);
                    if (isset($add_new->cpanelresult->error) && !empty($add_new->cpanelresult->error)) {
                        $error = array(
                            0 => array(
                                "result" => $add_new->cpanelresult->error
                            )
                        );
                        echo "<div class='alert alert-danger alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>{$error[0]['result']}</p>
			</div>";
                    } else {
                        echo "<div class='alert alert-success alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>" . Language::_("tastycpanel.success", true) . "</p>
			</div><script>$(document).ready(function (e) { $('#global_modal').modal('hide');});</script>";
                    }
                } else {
                    $error = array(
                        0 => array(
                            "result" => Language::_("tastycpanel.empty_invalid_values", true)
                        )
                    );
                    echo "<div class='alert alert-danger alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>{$error[0]['result']}</p>
			</div>";
                }
            } else {
                $customfiles = $this->customStyleJS();

                $this->Form->create("", array('onsubmit' => 'return false', 'id' => 'addform', 'autocomplete' => "off"));
                echo" {$customfiles}
            <div class='modal-body'>
<div class='div_response'></div>";

                echo'<div class="form-group">
   <label>' . Language::_("tastycpanel.ipblocker.ip", true) . '</label>
    <input type="text" class="form-control" value="" id="ip" name="ip" placeholder="e.g: 192.168.0.1"></div>
</div>
<div class="modal-footer">
<button type="button" name="cancel" class="btn btn-default" data-dismiss="modal"><i class="fa fa-ban"></i> ' . Language::_("tastycpanel.cancel", true) . '</button>
<button type="button" class="btn btn-primary" name="add_new" id="addnewsubmit"><i class="fa fa-plus-circle"></i> ' . Language::_("tastycpanel.submit", true) . '</button>
</div>
<script type="text/javascript">
    $(document).ready(function() {
        $("#addnewsubmit").click(function () {
    var form = $("#addform").serialize();
    doAjaxPost("' . $this->base_uri . "services/manage/" . $service->id . "/ipblocker/addnew/?" . '"+ form, form);
        });
    });
</script>


    ';
                $this->Form->end();
            }

            exit();
        }
    }

    public function backupsmain($package, $service, array $get = null, array $post = null, array $files = null) {
        $customfiles = $this->customStyleJS();
        $this->view = new View("backups", "default");
        $this->view->base_uri = $this->base_uri;
        Loader::loadHelpers($this, array("Form", "Html"));
        $service_fields = $this->serviceFieldsToObject($service->fields);
        $module_row = $this->getModuleRow($package->module_row);
        $cpanel = $this->getcPanelApi($module_row->meta->accesskey, $module_row->meta->hostname, $module_row->meta->username, $module_row->meta->use_ssl, false);


        $backups_list = $cpanel->api2_query($service_fields->username, "Backups", "listfullbackups", array());
        $this->view->set("module_row", $module_row);
        $this->view->set("service_fields", $service_fields);
        $this->view->set("backups_list", $backups_list->cpanelresult->data);
        $this->view->set("type", $package->meta->type);
        $this->view->set("service_id", $service->id);



        $this->view->setDefaultView("components" . DS . "modules" . DS . "tastycpanelmodule" . DS);
        return $customfiles . $this->view->fetch();
    }

    public function backupsaddnew($package, $service, array $get = null, array $post = null, array $files = null) {

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            Loader::loadHelpers($this, array("Form", "Html"));
            $service_fields = $this->serviceFieldsToObject($service->fields);
            $module_row = $this->getModuleRow($package->module_row);
            $cpanel = $this->getcPanelApi($module_row->meta->accesskey, $module_row->meta->hostname, $module_row->meta->username, $module_row->meta->use_ssl, false);
            if (isset($post) && !empty($post)) {
                if ($post['dest'] !== "") {
                    $input = array(
                        "dest" => $post['dest'],
                        "server" => $post['server'],
                        "user" => $post['user'],
                        "pass" => $post['pass'],
                        "email" => $post['email'],
                        "port" => $post['port'],
                        "rdir" => $post['rdir'],
                    );
                    if ($post['dest'] !== "homedir") {
                        if ($post['server'] !== "" && $post['user'] !== "" && $post['pass'] !== "" && $post['port'] !== "" && $post['rdir'] !== "") {
                            Loader::loadModels($this, array("Services"));
                            $add_new = $cpanel->api1_query($service_fields->username, "Fileman", "fullbackup", $input);
                            $this->log($module_row->meta->hostname . "|Generate New Backup", serialize("fullbackup"), "input", true);
                            echo "<div class='alert alert-success alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>" . Language::_("tastycpanel.success", true) . "</p>
			</div><script>$(document).ready(function (e) { $('#global_modal').modal('hide');});</script>";
                        } else {
                            $error = array(
                                0 => array(
                                    "result" => Language::_("tastycpanel.empty_invalid_values", true)
                                )
                            );
                            echo "<div class='alert alert-danger alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>{$error[0]['result']}</p>
			</div>";
                        }
                    } else {
                        Loader::loadModels($this, array("Services"));
                        $add_new = $cpanel->api1_query($service_fields->username, "Fileman", "fullbackup", $input);
                        $this->log($module_row->meta->hostname . "|Generate New Backup", serialize("fullbackup"), "input", true);
                        echo "<div class='alert alert-success alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>" . Language::_("tastycpanel.success", true) . "</p>
			</div><script>$(document).ready(function (e) { $('#global_modal').modal('hide');});</script>";
                    }
                } else {
                    $error = array(
                        0 => array(
                            "result" => Language::_("tastycpanel.empty_invalid_values", true)
                        )
                    );
                    echo "<div class='alert alert-danger alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>{$error[0]['result']}</p>
			</div>";
                }
            } else {
                $customfiles = $this->customStyleJS();

                $this->Form->create("", array('onsubmit' => 'return false', 'id' => 'addform', 'autocomplete' => "off"));
                echo" {$customfiles}
            <div class='modal-body'>
<div class='div_response'></div>";

                echo'<div class="form-group">
   <label>' . Language::_("tastycpanel.email.email_address", true) . '</label>
    <input type="email" class="form-control" value="" id="email" name="email" placeholder=""></div>
<div class="form-group">
   <label>' . Language::_("tastycpanel.backups.dest", true) . '</label>
<select name="dest" id="dest" class="form-control">
                <option value="homedir" selected="selected">Home Directory</option>
                <option value="ftp">Remote FTP Server</option>
                <option value="passiveftp">Remote FTP Server (passive mode transfer):</option>
                <option value="scp">Secure Copy (SCP)</option>
</select>
</div>
<div id="access_data" style="display:none;">
<div class="form-group">
   <label>' . Language::_("tastycpanel.backups.server", true) . '</label>
    <input type="text" class="form-control" value="" id="server" name="server" placeholder=""></div>
<div class="form-group">
   <label>' . Language::_("tastycpanel.backups.user", true) . '</label>
    <input type="text" class="form-control" value="" id="user" name="user" placeholder=""></div>
<div class="form-group">
   <label>' . Language::_("tastycpanel.backups.pass", true) . '</label>
    <input type="password" class="form-control" value="" id="pass" name="pass" placeholder=""></div>
<div class="form-group">
   <label>' . Language::_("tastycpanel.backups.port", true) . '</label>
    <input type="text" class="form-control" value="" id="port" name="port" placeholder=""></div>
<div class="form-group">
   <label>' . Language::_("tastycpanel.backups.rdir", true) . '</label>
    <input type="text" class="form-control" value="" id="rdir" name="rdir" placeholder=""></div>
</div>
</div>
<div class="modal-footer">
<button type="button" name="cancel" class="btn btn-default" data-dismiss="modal"><i class="fa fa-ban"></i> ' . Language::_("tastycpanel.cancel", true) . '</button>
<button type="button" class="btn btn-primary" name="add_new" id="addnewsubmit"><i class="fa fa-plus-circle"></i> ' . Language::_("tastycpanel.submit", true) . '</button>
</div>
<script type="text/javascript">
    $(document).ready(function() {
        $("#addnewsubmit").click(function () {
    var form = $("#addform").serialize();
    doAjaxPost("' . $this->base_uri . "services/manage/" . $service->id . "/backups/addnew/?" . '"+ form, form);
        });
        $("#dest").change(function () {
        if($(this).val() !== "homedir"){
    $("#access_data").css("display","block");
    } else {
    $("#access_data").css("display","none");
    }
        });
    });
</script>


    ';
                $this->Form->end();
            }

            exit();
        }
    }

    public function databasesmain($package, $service, array $get = null, array $post = null, array $files = null) {
        $customfiles = $this->customStyleJS();
        $this->view = new View("databases", "default");
        $this->view->base_uri = $this->base_uri;
        Loader::loadHelpers($this, array("Form", "Html"));
        $service_fields = $this->serviceFieldsToObject($service->fields);
        $module_row = $this->getModuleRow($package->module_row);
        $cpanel = $this->getcPanelApi($module_row->meta->accesskey, $module_row->meta->hostname, $module_row->meta->username, $module_row->meta->use_ssl, false);

        if (isset($post['delete_db'])) {
            if (!empty($post['db'])) {
                $delete_db = $cpanel->api2_query($service_fields->username, "MysqlFE", "deletedb", $post);
                $this->log($module_row->meta->hostname . "|Delete Database", serialize("deletedb"), "input", true);
            } else {
                $error = array(
                    0 => array(
                        "result" => Language::_("tastycpanel.empty_invalid_values", true)
                    )
                );
                $this->Input->setErrors($error[0]);
            }
        } else if (isset($post['revoke_user'])) {
            if (!empty($post['db']) && !empty($post['dbuser'])) {
                $delete_db = $cpanel->api2_query($service_fields->username, "MysqlFE", "revokedbuserprivileges", $post);
                $this->log($module_row->meta->hostname . "|revoke dbuser privileges", serialize("revokedbuserprivileges"), "input", true);
            } else {
                $error = array(
                    0 => array(
                        "result" => Language::_("tastycpanel.empty_invalid_values", true)
                    )
                );
                $this->Input->setErrors($error[0]);
            }
        } else if (isset($post['delete_dbuser'])) {
            if (!empty($post['dbuser'])) {
                $delete_db = $cpanel->api2_query($service_fields->username, "MysqlFE", "deletedbuser", $post);
                $this->log($module_row->meta->hostname . "|Delete DB User", serialize("deletedbuser"), "input", true);
            } else {
                $error = array(
                    0 => array(
                        "result" => Language::_("tastycpanel.empty_invalid_values", true)
                    )
                );
                $this->Input->setErrors($error[0]);
            }
        } else if (isset($post['delete_host'])) {
            if (!empty($post['host'])) {
                $delete_db = $cpanel->api2_query($service_fields->username, "MysqlFE", "deauthorizehost", $post);
                $this->log($module_row->meta->hostname . "|remove remote host's authorization", serialize("deauthorizehost"), "input", true);
            } else {
                $error = array(
                    0 => array(
                        "result" => Language::_("tastycpanel.empty_invalid_values", true)
                    )
                );
                $this->Input->setErrors($error[0]);
            }
        }


        $databases_list = $cpanel->api2_query($service_fields->username, "MysqlFE", "listdbs", array());

        $dbusers_list = $cpanel->api2_query($service_fields->username, "MysqlFE", "listusers", array());

        $remotedb_list = $cpanel->api2_query($service_fields->username, "MysqlFE", "listhosts", array());
        $this->view->set("module_row", $module_row);
        $this->view->set("service_fields", $service_fields);
        $this->view->set("databases_list", $databases_list->cpanelresult->data);
        $this->view->set("dbusers_list", $dbusers_list->cpanelresult->data);
        $this->view->set("remotedb_list", $remotedb_list->cpanelresult->data);
        $this->view->set("type", $package->meta->type);
        $this->view->set("service_id", $service->id);



        $this->view->setDefaultView("components" . DS . "modules" . DS . "tastycpanelmodule" . DS);
        return $customfiles . $this->view->fetch();
    }

    public function databasesaddnewdb($package, $service, array $get = null, array $post = null, array $files = null) {

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            Loader::loadHelpers($this, array("Form", "Html"));
            $service_fields = $this->serviceFieldsToObject($service->fields);
            $module_row = $this->getModuleRow($package->module_row);
            $cpanel = $this->getcPanelApi($module_row->meta->accesskey, $module_row->meta->hostname, $module_row->meta->username, $module_row->meta->use_ssl, false);
            if (isset($post) && !empty($post)) {
                if ($post['db'] !== "") {
                    $post['db'] = $service_fields->username . "_" . $post['db'];
                    Loader::loadModels($this, array("Services"));
                    $add_new = $cpanel->api2_query($service_fields->username, "MysqlFE", "createdb", $post);
                    $this->log($module_row->meta->hostname . "|Create New DB", serialize("createdb"), "input", true);

                    if (isset($add_new->cpanelresult->error) && !empty($add_new->cpanelresult->error)) {
                        $error = array(
                            0 => array(
                                "result" => $add_new->cpanelresult->error
                            )
                        );
                        echo "<div class='alert alert-danger alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>{$error[0]['result']}</p>
			</div>";
                    } else {
                        echo "<div class='alert alert-success alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>" . Language::_("tastycpanel.success", true) . "</p>
			</div><script>$(document).ready(function (e) { $('#global_modal').modal('hide');});</script>";
                    }
                } else {
                    $error = array(
                        0 => array(
                            "result" => Language::_("tastycpanel.empty_invalid_values", true)
                        )
                    );
                    echo "<div class='alert alert-danger alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>{$error[0]['result']}</p>
			</div>";
                }
            } else {
                $customfiles = $this->customStyleJS();

                $this->Form->create("", array('onsubmit' => 'return false', 'id' => 'addform', 'autocomplete' => "off"));
                echo" {$customfiles}
            <div class='modal-body'>
<div class='div_response'></div>";

                echo'<div class="form-group">
<label>' . Language::_("tastycpanel.db.database", true) . '</label>
<div class="input-group">
  <span class="input-group-addon" id="dir_s">' . $service_fields->username . '_</span>
    <input type="text" class="form-control" value="" id="db" name="db" placeholder=""></div>
</div>
</div>
<div class="modal-footer">
<button type="button" name="cancel" class="btn btn-default" data-dismiss="modal"><i class="fa fa-ban"></i> ' . Language::_("tastycpanel.cancel", true) . '</button>
<button type="button" class="btn btn-primary" name="add_new" id="addnewsubmit"><i class="fa fa-plus-circle"></i> ' . Language::_("tastycpanel.submit", true) . '</button>
</div>
<script type="text/javascript">
    $(document).ready(function() {
        $("#addnewsubmit").click(function () {
    var form = $("#addform").serialize();
    doAjaxPost("' . $this->base_uri . "services/manage/" . $service->id . "/databases/addnewdb/?" . '"+ form, form);
        });
    });
</script>


    ';
                $this->Form->end();
            }

            exit();
        }
    }

    public function databasesaddnewdbuser($package, $service, array $get = null, array $post = null, array $files = null) {

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            Loader::loadHelpers($this, array("Form", "Html"));
            $service_fields = $this->serviceFieldsToObject($service->fields);
            $module_row = $this->getModuleRow($package->module_row);
            $cpanel = $this->getcPanelApi($module_row->meta->accesskey, $module_row->meta->hostname, $module_row->meta->username, $module_row->meta->use_ssl, false);
            if (isset($post) && !empty($post)) {
                if ($post['dbuser'] !== "" && $post['password'] !== "") {
                    $post['dbuser'] = $service_fields->username . "_" . $post['dbuser'];
                    Loader::loadModels($this, array("Services"));
                    $add_new = $cpanel->api2_query($service_fields->username, "MysqlFE", "createdbuser", $post);
                    $this->log($module_row->meta->hostname . "|Create New DB User", serialize("createdbuser"), "input", true);
                    if (isset($add_new->cpanelresult->error) && !empty($add_new->cpanelresult->error)) {
                        $error = array(
                            0 => array(
                                "result" => $add_new->cpanelresult->error
                            )
                        );
                        echo "<div class='alert alert-danger alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>{$error[0]['result']}</p>
			</div>";
                    } else {
                        echo "<div class='alert alert-success alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>" . Language::_("tastycpanel.success", true) . "</p>
			</div><script>$(document).ready(function (e) { $('#global_modal').modal('hide');});</script>";
                    }
                } else {
                    $error = array(
                        0 => array(
                            "result" => Language::_("tastycpanel.empty_invalid_values", true)
                        )
                    );
                    echo "<div class='alert alert-danger alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>{$error[0]['result']}</p>
			</div>";
                }
            } else {
                $customfiles = $this->customStyleJS();

                $this->Form->create("", array('onsubmit' => 'return false', 'id' => 'addform', 'autocomplete' => "off"));
                echo" {$customfiles}
            <div class='modal-body'>
<div class='div_response'></div>";

                echo'<div class="form-group">
<label>' . Language::_("tastycpanel.db.dbuser", true) . '</label>
<div class="input-group">
  <span class="input-group-addon" id="dir_s">' . $service_fields->username . '_</span>
    <input type="text" class="form-control" value="" id="dbuser" name="dbuser" placeholder=""></div>
</div>
<div class="form-group">
   <label>' . Language::_("tastycpanel.service.password", true) . '</label>
    <input type="password" class="form-control" value="" id="password" name="password" placeholder="**********">
</div>
<div class="new_div"></div>

</div>
<div class="modal-footer">
<button type="button" name="cancel" class="btn btn-default" data-dismiss="modal"><i class="fa fa-ban"></i> ' . Language::_("tastycpanel.cancel", true) . '</button>
<button type="button" class="btn btn-default" id="generate"><i class="fa fa-key"></i> ' . Language::_("tastycpanel.service.generate", true) . '</button>
<button type="button" class="btn btn-primary" name="add_new" id="addnewsubmit"><i class="fa fa-plus-circle"></i> ' . Language::_("tastycpanel.submit", true) . '</button>
</div>
<script type="text/javascript">
    $(document).ready(function() {
        $("#addnewsubmit").click(function () {
    var form = $("#addform").serialize();
    doAjaxPost("' . $this->base_uri . "services/manage/" . $service->id . "/databases/addnewdbuser/?" . '"+ form, form);
        });
        $("#generate").click(function () {
            doAjaxGet("' . $this->base_uri . "services/manage/" . $service->id . "/changepassword/generatepass/" . '", "' . Language::_("tastycpanel.generated_password", true) . '");
        });
    });
</script>


    ';
                $this->Form->end();
            }

            exit();
        }
    }

    public function databasesaddnewremote($package, $service, array $get = null, array $post = null, array $files = null) {

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            Loader::loadHelpers($this, array("Form", "Html"));
            $service_fields = $this->serviceFieldsToObject($service->fields);
            $module_row = $this->getModuleRow($package->module_row);
            $cpanel = $this->getcPanelApi($module_row->meta->accesskey, $module_row->meta->hostname, $module_row->meta->username, $module_row->meta->use_ssl, false);
            if (isset($post) && !empty($post)) {
                if ($post['host'] !== "") {
                    Loader::loadModels($this, array("Services"));
                    $add_new = $cpanel->api2_query($service_fields->username, "MysqlFE", "authorizehost", $post);
                    $this->log($module_row->meta->hostname . "|Create Remote MySQL", serialize("authorizehost"), "input", true);
                    if (isset($add_new->cpanelresult->error) && !empty($add_new->cpanelresult->error)) {
                        $error = array(
                            0 => array(
                                "result" => $add_new->cpanelresult->error
                            )
                        );
                        echo "<div class='alert alert-danger alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>{$error[0]['result']}</p>
			</div>";
                    } else {
                        echo "<div class='alert alert-success alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>" . Language::_("tastycpanel.success", true) . "</p>
			</div><script>$(document).ready(function (e) { $('#global_modal').modal('hide');});</script>";
                    }
                } else {
                    $error = array(
                        0 => array(
                            "result" => Language::_("tastycpanel.empty_invalid_values", true)
                        )
                    );
                    echo "<div class='alert alert-danger alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>{$error[0]['result']}</p>
			</div>";
                }
            } else {
                $customfiles = $this->customStyleJS();

                $this->Form->create("", array('onsubmit' => 'return false', 'id' => 'addform', 'autocomplete' => "off"));
                echo" {$customfiles}
            <div class='modal-body'>
<div class='div_response'></div>";

                echo'<div class="form-group">
<label>' . Language::_("tastycpanel.db.remote_desc", true) . '</label>
    <input type="text" class="form-control" value="" id="host" name="host" placeholder="' . Language::_("tastycpanel.db.remote_desc", true) . '"></div>
</div>
<div class="modal-footer">
<button type="button" name="cancel" class="btn btn-default" data-dismiss="modal"><i class="fa fa-ban"></i> ' . Language::_("tastycpanel.cancel", true) . '</button>
<button type="button" class="btn btn-primary" name="add_new" id="addnewsubmit"><i class="fa fa-plus-circle"></i> ' . Language::_("tastycpanel.submit", true) . '</button>
</div>
<script type="text/javascript">
    $(document).ready(function() {
        $("#addnewsubmit").click(function () {
    var form = $("#addform").serialize();
    doAjaxPost("' . $this->base_uri . "services/manage/" . $service->id . "/databases/addnewremote/?" . '"+ form, form);
        });
    });
</script>


    ';
                $this->Form->end();
            }

            exit();
        }
    }

    public function databaseschangepass($package, $service, array $get = null, array $post = null, array $files = null) {

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            if (isset($get["dbuser"])) {
                Loader::loadHelpers($this, array("Form", "Html"));
                $service_fields = $this->serviceFieldsToObject($service->fields);
                $module_row = $this->getModuleRow($package->module_row);
                $cpanel = $this->getcPanelApi($module_row->meta->accesskey, $module_row->meta->hostname, $module_row->meta->username, $module_row->meta->use_ssl, false);
                if (isset($post) && !empty($post)) {
                    if ($post['dbuser'] !== "" && $post['password'] !== "") {
                        Loader::loadModels($this, array("Services"));
                        $change_quota = $cpanel->api2_query($service_fields->username, "MysqlFE", "changedbuserpassword", $post);
                        $this->log($module_row->meta->hostname . "|Change User Password", serialize("changedbuserpassword"), "input", true);
                        if (isset($change_quota->cpanelresult->error) && !empty($change_quota->cpanelresult->error)) {
                            $error = array(
                                0 => array(
                                    "result" => $change_quota->cpanelresult->error
                                )
                            );
                            echo "<div class='alert alert-danger alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>{$error[0]['result']}</p>
			</div>";
                        } else {
                            echo "<div class='alert alert-success alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>" . Language::_("tastycpanel.success", true) . "</p>
			</div><script>$(document).ready(function (e) { $('#global_modal').modal('hide');});</script>";
                        }
                    } else {
                        $error = array(
                            0 => array(
                                "result" => Language::_("tastycpanel.empty_invalid_values", true)
                            )
                        );
                        echo "<div class='alert alert-danger alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>{$error[0]['result']}</p>
			</div>";
                    }
                } else {
                    $customfiles = $this->customStyleJS();

                    $this->Form->create("", array('onsubmit' => 'return false', 'id' => 'changepassform', 'autocomplete' => "off"));
                    echo" {$customfiles}
            <div class='modal-body'>
<div class='div_response'></div>";
                    $this->Form->fieldHidden("dbuser", $this->Html->ifSet($get["dbuser"]), array('id' => "dbuser"));

                    echo'<div class="form-group">
   <label>' . Language::_("tastycpanel.changepassword", true) . " " . Language::_("tastycpanel.for", true) . " " . $get["dbuser"] . '</label>
    <input type="password" class="form-control" value="" id="password" name="password" placeholder="**********">
</div>
<div class="new_div"></div>
</div>
<div class="modal-footer">
<button type="button" name="cancel" class="btn btn-default" data-dismiss="modal"><i class="fa fa-ban"></i> ' . Language::_("tastycpanel.cancel", true) . '</button>
<button type="button" class="btn btn-default" id="generate"><i class="fa fa-key"></i> ' . Language::_("tastycpanel.service.generate", true) . '</button>
<button type="button" class="btn btn-primary" name="change_password" id="change_password"><i class="fa fa-edit"></i> ' . Language::_("tastycpanel.submit", true) . '</button>
</div>
<script type="text/javascript">
    $(document).ready(function () {
        $("#change_password").click(function () {
    var form = $("#changepassform").serialize();
    doAjaxPost("' . $this->base_uri . "services/manage/" . $service->id . "/databases/changepass/?" . '"+ form, form);
        });
        $("#generate").click(function () {
            doAjaxGet("' . $this->base_uri . "services/manage/" . $service->id . "/changepassword/generatepass/" . '", "' . Language::_("tastycpanel.generated_password", true) . '");
        });
    });
</script>


    ';
                    $this->Form->end();
                }

                exit();
            } else {
                return false;
            }
        }
    }

    public function databasesaddusertodb($package, $service, array $get = null, array $post = null, array $files = null) {

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            if (isset($get["dbuser"])) {
                Loader::loadHelpers($this, array("Form", "Html"));
                $service_fields = $this->serviceFieldsToObject($service->fields);
                $module_row = $this->getModuleRow($package->module_row);
                $cpanel = $this->getcPanelApi($module_row->meta->accesskey, $module_row->meta->hostname, $module_row->meta->username, $module_row->meta->use_ssl, false);
                if (isset($post) && !empty($post)) {

                    if ($post['db'] !== "" && $post['dbuser'] !== "" && $post['privileges'] !== "") {
                        $privileges = "";
                        foreach ($post['privileges'] as $key => $value) {
                            $privileges .= " {$post['privileges'][$key]}";
                        }
                        $post['privileges'] = $privileges;
                        Loader::loadModels($this, array("Services"));
                        $add_new = $cpanel->api2_query($service_fields->username, "MysqlFE", "setdbuserprivileges", $post);
                        $this->log($module_row->meta->hostname . "|Grant privileges to a user on a database", serialize("setdbuserprivileges"), "input", true);
                        if (isset($add_new->cpanelresult->error) && !empty($add_new->cpanelresult->error)) {
                            $error = array(
                                0 => array(
                                    "result" => $add_new->cpanelresult->error
                                )
                            );
                            echo "<div class='alert alert-danger alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>{$error[0]['result']}</p>
			</div>";
                        } else {
                            echo "<div class='alert alert-success alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>" . Language::_("tastycpanel.success", true) . "</p>
			</div><script>$(document).ready(function (e) { $('#global_modal').modal('hide');});</script>";
                        }
                    } else {
                        $error = array(
                            0 => array(
                                "result" => Language::_("tastycpanel.empty_invalid_values", true)
                            )
                        );
                        echo "<div class='alert alert-danger alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>{$error[0]['result']}</p>
			</div>";
                    }
                } else {
                    $customfiles = $this->customStyleJS();
                    $databases_list = $cpanel->api2_query($service_fields->username, "MysqlFE", "listdbs", array());
                    $select_option = "<select name='db' class='form-control' id='db'>";
                    foreach ($databases_list->cpanelresult->data as $key => $value) {
                        $select_option .= "<option value='{$databases_list->cpanelresult->data[$key]->db}'>{$databases_list->cpanelresult->data[$key]->db}</option>";
                    }
                    $select_option .= "</select>";


                    $this->Form->create("", array('onsubmit' => 'return false', 'id' => 'addform', 'autocomplete' => "off"));
                    echo" {$customfiles}
            <div class='modal-body'>
<div class='div_response'></div>";
                    $this->Form->fieldHidden("dbuser", $this->Html->ifSet($get["dbuser"]), array('id' => "dbuser"));

                    echo'<div class="form-group">
   <label>' . Language::_("tastycpanel.db.adduser", true) . " " . $get["dbuser"] . " " . Language::_("tastycpanel.db.to_database", true) . '</label>
' . $select_option . '
</div>
<div class="form-group">
<label>' . Language::_("tastycpanel.db.privileges_user", true) . '</label>
<select name="privileges[]" id="privileges" class="form-control" multiple class="form-control">
                <option value="ALL PRIVILEGES" selected="selected">ALL PRIVILEGES</option>
                <option value="ALTER">ALTER</option>
                <option value="ALTER ROUTINE">ALTER ROUTINE</option>
                <option value="CREATE">CREATE</option>
                <option value="CREATE ROUTINE">CREATE ROUTINE</option>
                <option value="CREATE TEMPORARY TABLES">CREATE TEMPORARY TABLES</option>
                <option value="CREATE VIEW">CREATE VIEW</option>
                <option value="DELETE">DELETE</option>
                <option value="DROP">DROP</option>
                <option value="EVENT">EVENT</option>
                <option value="EXECUTE">EXECUTE</option>
                <option value="INDEX">INDEX</option>
                <option value="INSERT">INSERT</option>
                <option value="LOCK TABLES">LOCK TABLES</option>
                <option value="REFERENCES">REFERENCES</option>
                <option value="SELECT">SELECT</option>
                <option value="SHOW VIEW">SHOW VIEW</option>
                <option value="TRIGGER">TRIGGER</option>
                <option value="UPDATE">UPDATE</option>
</select>
</div>
<div class="modal-footer">
<button type="button" name="cancel" class="btn btn-default" data-dismiss="modal"><i class="fa fa-ban"></i> ' . Language::_("tastycpanel.cancel", true) . '</button>
<button type="button" class="btn btn-primary" name="add_new" id="addnewsubmit"><i class="fa fa-plus-circle"></i> ' . Language::_("tastycpanel.submit", true) . '</button>
</div>
<script type="text/javascript">
    $(document).ready(function() {
        $("#addnewsubmit").click(function () {
    var form = $("#addform").serialize();
    doAjaxPost("' . $this->base_uri . "services/manage/" . $service->id . "/databases/addusertodb/?" . '"+ form, form);
        });
    });
</script>


    ';
                    $this->Form->end();
                }

                exit();
            } else {
                return false;
            }
        }
    }

    public function manageappsmain($package, $service, array $get = null, array $post = null, array $files = null) {
        $customfiles = $this->customStyleJS();
        $this->view = new View("manageapps_softaculous", "default");
        $this->view->base_uri = $this->base_uri;
        Loader::loadHelpers($this, array("Form", "Html"));
        $service_fields = $this->serviceFieldsToObject($service->fields);
        $module_row = $this->getModuleRow($package->module_row);
        $api = $this->getsoftaApi($package, $service, $module_row->meta->hostname, $service_fields->username, $service_fields->password);
        if (isset($post['submitremovebu'])) {
            $remove_backup = $api->remove_backup($post['filename']);
        } else if (isset($post['submitmakebu'])) {
            $make_backup = $api->backup($post['installid']);
        } else if (isset($post['submitrestorebu'])) {
            $restore = $api->restore($post['filename']);
        } else if (isset($post['submitdeleteinstall'])) {
            $remove = $api->remove($post['installid']);
        } else if (isset($post['submitupgrade'])) {
            $upgrade = $api->upgrade($post['installid']);
        }
        $getallscripts = $api->list_scripts();
        $result_backups = $api->list_backups();
        $installations = $api->installations();
        $this->view->set("result_backups", $result_backups);
        $this->view->set("availabledomain", $this->getAvailableDomains($package, $service));
        $this->view->set("availablescripts", $this->scriptsavailable($package, $service));
        $this->view->set("user_type", $package->meta->type);
        $this->view->set("module_row", $module_row);
        $this->view->set("service_fields", $service_fields);
        $this->view->set("installations", $installations);
        $this->view->set("listscripts", $api->scripts);
        $this->view->set("cpdomain", $service_fields->domain_name);
        $this->view->set("cpusername", $service_fields->username);
        $this->view->set("service_id", $service->id);



        $this->view->setDefaultView("components" . DS . "modules" . DS . "tastycpanelmodule" . DS);
        return $customfiles . $this->view->fetch();
    }

    public function manageappsinstall($package, $service, array $get = null, array $post = null, array $files = null) {

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            Loader::loadHelpers($this, array("Form", "Html"));
            $service_fields = $this->serviceFieldsToObject($service->fields);
            $module_row = $this->getModuleRow($package->module_row);
            $cpanel = $this->getcPanelApi($module_row->meta->accesskey, $module_row->meta->hostname, $module_row->meta->username, $module_row->meta->use_ssl, false);
            if (isset($post) && !empty($post)) {
                if ($post['softdomain'] !== "" && $post['softdirectory'] !== "" && $post['admin_username'] !== "" && $post['admin_pass'] !== "" && $post['admin_email'] !== "" && $post['softdb'] !== "" && $post['dbusername'] !== "" && $post['dbuserpass'] !== "" && $post['language'] !== "" && $post['site_name'] !== "" && $post['site_desc'] !== "") {
                    Loader::loadModels($this, array("Services"));
                    $api = $this->getsoftaApi($package, $service, $module_row->meta->hostname, $service_fields->username, $service_fields->password);
                    $install_script = $api->install($post['scriptid'], $post);
                    $res = unserialize($install_script);
                    if (empty($res['error'])) {

                        echo "<div class='alert alert-success alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>" . Language::_("tastycpanel.success", true) . "</p>
			</div><script>$(document).ready(function (e) { $('#global_modal').modal('hide');});</script>";
                    } else {
                        foreach ($res['error'] as $key => $value) {
                            echo "<div class='alert alert-danger alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>{$res['error'][$key]}</p>
			</div>";
                        }
                    }
                } else {
                    $error = array(
                        0 => array(
                            "result" => Language::_("tastycpanel.empty_invalid_values", true)
                        )
                    );
                    echo "<div class='alert alert-danger alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>{$error[0]['result']}</p>
			</div>";
                }
            } else {
                $customfiles = $this->customStyleJS();

                $this->Form->create("", array('onsubmit' => 'return false', 'id' => 'addform', 'autocomplete' => "off"));
                echo" {$customfiles}
            <div class='modal-body'>
<div class='div_response'></div>";

                echo'<div class="form-group">
   <label>' . Language::_("tastycpanel.manageapps.choose_software", true) . '</label>
<select name="scriptid" id="scriptid" class="form-control">
' . $this->scriptsavailable($package, $service) . '
</select>
</div>
<div class="form-group">
   <label>' . Language::_("tastycpanel.service.domain", true) . '</label>
<select name="softdomain" id="softdomain" class="form-control">
' . $this->getAvailableDomains($package, $service) . '
</select>
</div>
<div class="form-group">
   <label>' . Language::_("tastycpanel.manageapps.software_directory_addapp", true) . '</label>
    <input type="text" class="form-control" value="" id="softdirectory" name="softdirectory" placeholder=""></div>
<div class="form-group">
   <label>' . Language::_("tastycpanel.manageapps.swadmin_username", true) . '</label>
    <input type="text" class="form-control" value="" id="admin_username" name="admin_username" placeholder=""></div>
<div class="form-group">
   <label>' . Language::_("tastycpanel.manageapps.swadmin_pass", true) . '</label>
    <input type="password" class="form-control" value="" id="admin_pass" name="admin_pass" placeholder=""></div>
<div class="form-group">
   <label>' . Language::_("tastycpanel.manageapps.swadmin_email", true) . '</label>
    <input type="text" class="form-control" value="" id="admin_email" name="admin_email" placeholder=""></div>
<div class="form-group">
   <label>' . Language::_("tastycpanel.db.database", true) . '</label>
<div class="input-group">
  <span class="input-group-addon" id="dir_s">' . $service_fields->username . '_</span>
    <input type="text" class="form-control" value="" id="softdb" name="softdb" placeholder=""></div>
</div>
<div class="form-group">
   <label>' . Language::_("tastycpanel.db.dbuser", true) . '</label>
<div class="input-group">
  <span class="input-group-addon" id="dir_s">' . $service_fields->username . '_</span>
    <input type="text" class="form-control" value="" id="dbusername" name="dbusername" placeholder=""></div>
</div>
<div class="form-group">
   <label>' . Language::_("tastycpanel.password", true) . '</label>
    <input type="password" class="form-control" value="" id="dbuserpass" name="dbuserpass" placeholder=""></div>
<div class="form-group">
   <label>' . Language::_("tastycpanel.manageapps.sw_site_name", true) . '</label>
    <input type="text" class="form-control" value="" id="site_name" name="site_name" placeholder=""></div>
<div class="form-group">
   <label>' . Language::_("tastycpanel.manageapps.sw_site_desc", true) . '</label>
    <input type="text" class="form-control" value="" id="site_desc" name="site_desc" placeholder=""></div>
<div class="form-group">
   <label>' . Language::_("tastycpanel.manageapps.language", true) . '</label>
<select name="language" id="language" class="form-control">
<option value="en">English</option>
<option value="ar">Arabic</option>
<option value="bg_BG">Bulgarian</option>
<option value="ca">Catalan</option>
<option value="zh_CN">Chinese(Simplified)</option>
<option value="zh_TW">Chinese(Traditional)</option>
<option value="hr">Croatian</option>
<option value="cs_CZ">Czech</option>
<option value="da_DK">Danish</option>
<option value="nl_NL">Dutch</option>
<option value="fi">Finnish</option>
<option value="fr_FR">French</option>
<option value="de_DE">German</option>
<option value="el">Greek</option>
<option value="he_IL">Hebrew</option>
<option value="hu_HU">Hungarian</option>
<option value="id_ID">Indonesian</option>
<option value="it_IT">Italian</option>
<option value="ja">Japanese</option>
<option value="ko_KR">Korean</option>
<option value="nb_NO">Norwegian</option>
<option value="fa_IR">Persian</option>
<option value="pl_PL">Polish</option>
<option value="pt_PT">Portuguese</option>
<option value="pt_BR">Portuguese-BR</option>
<option value="ro_RO">Romanian</option>
<option value="ru_RU">Russian</option>
<option value="sl_SI">Slovenian</option>
<option value="es_ES">Spanish</option>
<option value="sv_SE">Swedish</option>
<option value="th">Thai</option>
<option value="tr_TR">Turkish</option>
<option value="uk">Ukrainian</option>
</select>
</div>

</div>
<div class="modal-footer">
<button type="button" name="cancel" class="btn btn-default" data-dismiss="modal"><i class="fa fa-ban"></i> ' . Language::_("tastycpanel.cancel", true) . '</button>
<button type="button" class="btn btn-primary" name="add_new" id="addnewsubmit"><i class="fa fa-plus-circle"></i> ' . Language::_("tastycpanel.submit", true) . '</button>
</div>
<script type="text/javascript">
    $(document).ready(function() {
        $("#addnewsubmit").click(function () {
    var form = $("#addform").serialize();
    doAjaxPost("' . $this->base_uri . "services/manage/" . $service->id . "/manageapps/installapps/?" . '"+ form, form);
        });
    });
</script>


    ';
                $this->Form->end();
            }

            exit();
        }
    }

    public function generatepass($package, $service, array $get = null, array $post = null, array $files = null) {

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            echo '<div class="form-group">
            <label for="">Copy &amp; Paste The Following Password</label>
            <input type="text" class="form-control" readonly="readonly" value="' . $this->generatePassword() . '">
            </div>';

            exit();
        } else {
            return false;
        }
    }

    public function manageappsmaininstallatron($package, $service, array $get = null, array $post = null, array $files = null) {
        $customfiles = $this->customStyleJS();
        $this->view = new View("manageapps_installatron", "default");
        $this->view->base_uri = $this->base_uri;
        Loader::loadHelpers($this, array("Form", "Html"));
        $service_fields = $this->serviceFieldsToObject($service->fields);
        $module_row = $this->getModuleRow($package->module_row);
        if (isset($post['submitremovebu'])) {
            $installid = array(
                "cmd" => "delete",
                "id" => "{$post['installid']}"
            );
            $backup_install = $this->doInstallatronConnecting($installid, $module_row->meta->hostname, $service_fields->username, $service_fields->password);
        } else if (isset($post['submitmakebu'])) {
            $post_data = array(
                "installid" => "{$post['installid']}"
            );
            $installid = array(
                "cmd" => "backup",
                "id" => "{$post_data['installid']}"
            );
            $backup_install = $this->doInstallatronConnecting($installid, $module_row->meta->hostname, $service_fields->username, $service_fields->password);
            if ($backup_install["result"] === false) {
                $fa = array(
                    0 => array(
                        "result" => "Unable to generate a backup for your Installation"
                    )
                );
                $this->Input->setErrors($fa[0]["result"]);
                $this->log("Installatron App Backup Failed: {$doinstall['message']}", serialize($service_fields->username), "input", true);
            }
        } else if (isset($post['submitrestorebu'])) {
            $post_data = array(
                "installid" => "{$post['installid']}"
            );
            $installid = array(
                "cmd" => "restore",
                "id" => "{$post_data['installid']}"
            );
            $backup_install = $this->doInstallatronConnecting($installid, $module_row->meta->hostname, $service_fields->username, $service_fields->password);
        } else if (isset($post['submitdeleteinstall'])) {
            $post_data = array(
                "installid" => "{$post['installid']}"
            );
            $installid = array(
                "cmd" => "uninstall",
                "id" => "{$post_data['installid']}"
            );
            $delete_install = $this->doInstallatronConnecting($installid, $module_row->meta->hostname, $service_fields->username, $service_fields->password);
        } else if (isset($post['submitupgrade'])) {
            $post_data = array(
                "installid" => "{$post['installid']}"
            );
            $installid = array(
                "cmd" => "upgrade",
                "id" => "{$post_data['installid']}"
            );
            $upgrade_install = $this->doInstallatronConnecting($installid, $module_row->meta->hostname, $service_fields->username, $service_fields->password);
        }
        $getback = array(
            "cmd" => "backups"
        );
        $result_backups = $this->doInstallatronConnecting($getback, $module_row->meta->hostname, $service_fields->username, $service_fields->password);
        $installlistingquery = array(
            "cmd" => "installs"
        );
        $getallinstallations = $this->doInstallatronConnecting($installlistingquery, $module_row->meta->hostname, $service_fields->username, $service_fields->password);
        $this->view->set("availabledomain", $this->getAvailableDomains($package, $service));
        $this->view->set("availablescripts", $this->availableinstallatronscipts($package, $service));
        $this->view->set("result", $getallinstallations);
        $this->view->set("result_backups", $result_backups);
        $this->view->set("user_type", $package->meta->type);
        $this->view->set("module_row", $module_row);
        $this->view->set("service_fields", $service_fields);
        $this->view->set("cpdomain", $service_fields->domain_name);
        $this->view->set("cpusername", $service_fields->username);
        $this->view->set("service_id", $service->id);



        $this->view->setDefaultView("components" . DS . "modules" . DS . "tastycpanelmodule" . DS);
        return $customfiles . $this->view->fetch();
    }

    public function manageappsinstallinstallatron($package, $service, array $get = null, array $post = null, array $files = null) {

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            Loader::loadHelpers($this, array("Form", "Html"));
            $service_fields = $this->serviceFieldsToObject($service->fields);
            $module_row = $this->getModuleRow($package->module_row);
            $cpanel = $this->getcPanelApi($module_row->meta->accesskey, $module_row->meta->hostname, $module_row->meta->username, $module_row->meta->use_ssl, false);
            if (isset($post) && !empty($post)) {
                if ($post['softdomain'] !== "" && $post['softdirectory'] !== "" && $post['admin_username'] !== "" && $post['admin_pass'] !== "" && $post['admin_email'] !== "" && $post['language'] !== "" && $post['site_name'] !== "") {
                    Loader::loadModels($this, array("Services"));
                    $installlistingquery = array(
                        "cmd" => "install",
                        "&application" => "{$post['scriptid']}",
                        "url" => "http://{$post['softdomain']}/{$post['softdirectory']}",
                        "sitetitle" => $post['site_name'],
                        "language" => $post['language'],
                        "login" => $post['admin_username'],
                        "db" => "auto",
                        "passwd" => $post['admin_pass']
                    );
                    $doinstall = $this->doInstallatronConnecting($installlistingquery, $module_row->meta->hostname, $service_fields->username, $service_fields->password);
                    if (!empty($doinstall['data'])) {

                        echo "<div class='alert alert-success alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>" . Language::_("tastycpanel.success", true) . "</p>
			</div><script>$(document).ready(function (e) { $('#global_modal').modal('hide');});</script>";
                    } else {
                        $error = array(
                            0 => array(
                                "result" => "Installation Failed"
                            )
                        );
                        echo "<div class='alert alert-danger alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>{$error[0]['result']}</p>
			</div>";
                        $this->log("Installatron App Installation Failed: {$doinstall['message']}", serialize($service_fields->username), "input", true);
                    }
                } else {
                    $error = array(
                        0 => array(
                            "result" => Language::_("tastycpanel.empty_invalid_values", true)
                        )
                    );
                    echo "<div class='alert alert-danger alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>{$error[0]['result']}</p>
			</div>";
                }
            } else {
                $customfiles = $this->customStyleJS();

                $this->Form->create("", array('onsubmit' => 'return false', 'id' => 'addform', 'autocomplete' => "off"));
                echo" {$customfiles}
            <div class='modal-body'>
<div class='div_response'></div>";

                echo'<div class="form-group">
   <label>' . Language::_("tastycpanel.manageapps.choose_software", true) . '</label>
<select name="scriptid" id="scriptid" class="form-control">
' . $this->availableinstallatronscipts($package, $service) . '
</select>
</div>
<div class="form-group">
   <label>' . Language::_("tastycpanel.service.domain", true) . '</label>
<select name="softdomain" id="softdomain" class="form-control">
' . $this->getAvailableDomains($package, $service) . '
</select>
</div>
<div class="form-group">
   <label>' . Language::_("tastycpanel.manageapps.software_directory_addapp", true) . '</label>
    <input type="text" class="form-control" value="" id="softdirectory" name="softdirectory" placeholder=""></div>
<div class="form-group">
   <label>' . Language::_("tastycpanel.manageapps.swadmin_username", true) . '</label>
    <input type="text" class="form-control" value="" id="admin_username" name="admin_username" placeholder=""></div>
<div class="form-group">
   <label>' . Language::_("tastycpanel.manageapps.swadmin_pass", true) . '</label>
    <input type="password" class="form-control" value="" id="admin_pass" name="admin_pass" placeholder=""></div>
<div class="form-group">
   <label>' . Language::_("tastycpanel.manageapps.swadmin_email", true) . '</label>
    <input type="text" class="form-control" value="" id="admin_email" name="admin_email" placeholder=""></div>
<div class="form-group">
   <label>' . Language::_("tastycpanel.manageapps.sw_site_name", true) . '</label>
    <input type="text" class="form-control" value="" id="site_name" name="site_name" placeholder=""></div>
<div class="form-group">
   <label>' . Language::_("tastycpanel.manageapps.language", true) . '</label>
    <input type="text" class="form-control" value="" id="language" name="language" placeholder="e.g: en"></div>
</div>
<div class="modal-footer">
<button type="button" name="cancel" class="btn btn-default" data-dismiss="modal"><i class="fa fa-ban"></i> ' . Language::_("tastycpanel.cancel", true) . '</button>
<button type="button" class="btn btn-primary" name="add_new" id="addnewsubmit"><i class="fa fa-plus-circle"></i> ' . Language::_("tastycpanel.submit", true) . '</button>
</div>
<script type="text/javascript">
    $(document).ready(function() {
        $("#addnewsubmit").click(function () {
    var form = $("#addform").serialize();
    doAjaxPost("' . $this->base_uri . "services/manage/" . $service->id . "/manageapps/installapps/?" . '"+ form, form);
        });
    });
</script>


    ';
                $this->Form->end();
            }

            exit();
        }
    }

    public function listaccountsmain($package, $service, array $get = null, array $post = null, array $files = null) {
        $customfiles = $this->customStyleJS();
        $this->view = new View("list_accounts", "default");
        $this->view->base_uri = $this->base_uri;
        Loader::loadHelpers($this, array("Form", "Html"));
        $service_fields = $this->serviceFieldsToObject($service->fields);
        $module_row = $this->getModuleRow($package->module_row);
        $cpanel = $this->getcPanelApi($module_row->meta->accesskey, $module_row->meta->hostname, $module_row->meta->username, $module_row->meta->use_ssl, false);
        $access_key = $cpanel->getResellerAccessKey(array('host' => $module_row->meta->hostname, 'username' => $service_fields->username, 'password' => $service_fields->password));
        $reseller = $this->getcPanelApi($access_key, $module_row->meta->hostname, $service_fields->username, $module_row->meta->use_ssl, false);


        if (isset($post['suspend'])) {
            if (!empty($post['user'])) {
                $suspend = $reseller->suspendacct($post);
                $this->log($module_row->meta->hostname . "|Suspend a reseller {$service_fields->username} child account", serialize("suspendacct"), "input", true);
            } else {
                $error = array(
                    0 => array(
                        "result" => Language::_("tastycpanel.empty_invalid_values", true)
                    )
                );
                $this->Input->setErrors($error[0]);
            }
        } else if (isset($post['unsuspend'])) {
            if (!empty($post['user'])) {
                $suspend = $reseller->unsuspendacct($post);
                $this->log($module_row->meta->hostname . "|Unuspend a reseller {$service_fields->username} child account", serialize("unsuspendacct"), "input", true);
            } else {
                $error = array(
                    0 => array(
                        "result" => Language::_("tastycpanel.empty_invalid_values", true)
                    )
                );
                $this->Input->setErrors($error[0]);
            }
        } else if (isset($post['killpkg'])) {
            if (!empty($post['name'])) {
                $killpkg = $reseller->killpkg($post);
                $this->log($module_row->meta->hostname . "|Kill a Reseller {$service_fields->username} Package", serialize("killpkg"), "input", true);
            } else {
                $error = array(
                    0 => array(
                        "result" => Language::_("tastycpanel.empty_invalid_values", true)
                    )
                );
                $this->Input->setErrors($error[0]);
            }
        }




        $acc_list = $reseller->listaccts("owner", "{$service_fields->username}");
        $package_list = $reseller->listpkgs();

        $this->view->set("module_row", $module_row);
        $this->view->set("service_fields", $service_fields);
        $this->view->set("package_list", $package_list);
        $this->view->set("acc_list", $acc_list);
        $this->view->set("type", $package->meta->type);
        $this->view->set("service_id", $service->id);



        $this->view->setDefaultView("components" . DS . "modules" . DS . "tastycpanelmodule" . DS);
        return $customfiles . $this->view->fetch();
    }

    public function listaccountseditpkg($package, $service, array $get = null, array $post = null, array $files = null) {

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            if (isset($get["name"])) {
                Loader::loadHelpers($this, array("Form", "Html"));
                $service_fields = $this->serviceFieldsToObject($service->fields);
                $module_row = $this->getModuleRow($package->module_row);
                $cpanel = $this->getcPanelApi($module_row->meta->accesskey, $module_row->meta->hostname, $module_row->meta->username, $module_row->meta->use_ssl, false);
                $access_key = $cpanel->getResellerAccessKey(array('host' => $module_row->meta->hostname, 'username' => $service_fields->username, 'password' => $service_fields->password));
                $reseller = $this->getcPanelApi($access_key, $module_row->meta->hostname, $service_fields->username, $module_row->meta->use_ssl, false);
                $getpkginfo = $reseller->getpkginfo(array("pkg" => $get["name"]));
                if (isset($post) && !empty($post)) {
                    if ($post['quota'] !== "" && $post['bwlimit'] !== "" && $post['maxftp'] !== "" && $post['maxpop'] !== "" && $post['maxlists'] !== "" && $post['maxsql'] !== "" && $post['maxsub'] !== "" && $post['maxpark'] !== "" && $post['maxaddon'] !== "" && $post['MAX_EMAIL_PER_HOUR'] !== "" && $post['MAX_DEFER_FAIL_PERCENTAGE'] !== "") {
                        Loader::loadModels($this, array("Services"));
                        $add_new = $reseller->addpkg($post);
                        $this->log($module_row->meta->hostname . "|Create New Package By a Reseller {$service_fields->username}", serialize("addpkg"), "input", true);
                        echo "<div class='alert alert-success alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>" . Language::_("tastycpanel.success", true) . "</p>
			</div><script>$(document).ready(function (e) { $('#global_modal').modal('hide');});</script>";
                    } else {
                        $error = array(
                            0 => array(
                                "result" => Language::_("tastycpanel.empty_invalid_values", true)
                            )
                        );
                        echo "<div class='alert alert-danger alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>{$error[0]['result']}</p>
			</div>";
                    }
                } else {
                    $customfiles = $this->customStyleJS();
                    $package_list = $reseller->listpkgs();
                    $pkglist = "";
                    if (is_array($package_list)) {
                        foreach ($package_list as $key => $value) {
                            $pkglist .= "<option value='{$package_list[$key]->name}'>{$package_list[$key]->name}</option>";
                        }
                    }

                    $this->Form->create("", array('onsubmit' => 'return false', 'id' => 'editform', 'autocomplete' => "off"));
                    echo" {$customfiles}
            <div class='modal-body'>
<div class='div_response'></div>";
                    echo'<div class="form-group">
   <label>' . Language::_("tastycpanel.listaccounts.pkgname", true) . '</label>
    <input type="text" class="form-control" value="' . $get["name"] . '" id="name" name="name" disabled="" placeholder=""></div>
<div class="form-group">
   <label>' . Language::_("tastycpanel.listaccounts.quota", true) . '(MB)</label>
    <input type="text" class="form-control" value="' . $getpkginfo->quota . '" id="quota" name="quota" placeholder="e.g: 250 or unlimited"></div>
<div class="form-group">
   <label>' . Language::_("tastycpanel.listaccounts.bwlimit", true) . '(MB)</label>
    <input type="text" class="form-control" value="' . $getpkginfo->bwlimit . '" id="bwlimit" name="bwlimit" placeholder="e.g: 250 or unlimited">
</div>
<div class="form-group">
   <label>' . Language::_("tastycpanel.listaccounts.maxftp", true) . '</label>
    <input type="text" class="form-control" value="' . $getpkginfo->maxftp . '" id="maxftp" name="maxftp" placeholder="e.g: 5 or unlimited">
</div>
<div class="form-group">
   <label>' . Language::_("tastycpanel.listaccounts.maxemail", true) . '</label>
    <input type="text" class="form-control" value="' . $getpkginfo->maxpop . '" id="maxpop" name="maxpop" placeholder="e.g: 5 or unlimited">
</div>
<div class="form-group">
   <label>' . Language::_("tastycpanel.listaccounts.maxemaillist", true) . '</label>
    <input type="text" class="form-control" value="' . $getpkginfo->maxlists . '" id="maxlists" name="maxlists" placeholder="e.g: 5 or unlimited">
</div>
<div class="form-group">
   <label>' . Language::_("tastycpanel.listaccounts.maxdb", true) . '</label>
    <input type="text" class="form-control" value="' . $getpkginfo->maxsql . '" id="maxsql" name="maxsql" placeholder="e.g: 5 or unlimited">
</div>
<div class="form-group">
   <label>' . Language::_("tastycpanel.listaccounts.maxsubdomains", true) . '</label>
    <input type="text" class="form-control" value="' . $getpkginfo->maxsub . '" id="maxsub" name="maxsub" placeholder="e.g: 5 or unlimited">
</div>
<div class="form-group">
   <label>' . Language::_("tastycpanel.listaccounts.maxparkeddomains", true) . '</label>
    <input type="text" class="form-control" value="' . $getpkginfo->maxpark . '" id="maxpark" name="maxpark" placeholder="e.g: 5 or unlimited">
</div>
<div class="form-group">
   <label>' . Language::_("tastycpanel.listaccounts.maxaddondomains", true) . '</label>
    <input type="text" class="form-control" value="' . $getpkginfo->maxaddon . '" id="maxaddon" name="maxaddon" placeholder="e.g: 5 or unlimited">
</div>
<div class="form-group">
   <label>' . Language::_("tastycpanel.listaccounts.maxhourlyemail", true) . '</label>
    <input type="text" class="form-control" value="' . $getpkginfo->MAX_EMAIL_PER_HOUR . '" id="MAX_EMAIL_PER_HOUR" name="MAX_EMAIL_PER_HOUR" placeholder="">
</div>
<div class="form-group">
   <label>' . Language::_("tastycpanel.listaccounts.maxpercent", true) . '</label>
    <input type="text" class="form-control" value="' . $getpkginfo->MAX_DEFER_FAIL_PERCENTAGE . '" id="MAX_DEFER_FAIL_PERCENTAGE" name="MAX_DEFER_FAIL_PERCENTAGE" placeholder="">
</div>
</div>
<div class="modal-footer">
<button type="button" name="cancel" class="btn btn-default" data-dismiss="modal"><i class="fa fa-ban"></i> ' . Language::_("tastycpanel.cancel", true) . '</button>
<button type="button" class="btn btn-primary" name="edit" id="editsubmit"><i class="fa fa-plus-circle"></i> ' . Language::_("tastycpanel.submit", true) . '</button>
</div>
<script type="text/javascript">
    $(document).ready(function() {
        $("#editsubmit").click(function () {
    var form = $("#editform").serialize();
    doAjaxPost("' . $this->base_uri . "services/manage/" . $service->id . "/listaccounts/addpkg/?" . '"+ form, form);
        });
    });
</script>
    ';
                    $this->Form->end();
                }

                exit();
            } else {
                return false;
            }
        }
    }

    public function listaccountsaddpkg($package, $service, array $get = null, array $post = null, array $files = null) {

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            Loader::loadHelpers($this, array("Form", "Html"));
            $service_fields = $this->serviceFieldsToObject($service->fields);
            $module_row = $this->getModuleRow($package->module_row);
            $cpanel = $this->getcPanelApi($module_row->meta->accesskey, $module_row->meta->hostname, $module_row->meta->username, $module_row->meta->use_ssl, false);
            $access_key = $cpanel->getResellerAccessKey(array('host' => $module_row->meta->hostname, 'username' => $service_fields->username, 'password' => $service_fields->password));
            $reseller = $this->getcPanelApi($access_key, $module_row->meta->hostname, $service_fields->username, $module_row->meta->use_ssl, false);
            if (isset($post) && !empty($post)) {
                if ($post['name'] !== "" && $post['quota'] !== "" && $post['bwlimit'] !== "" && $post['maxftp'] !== "" && $post['maxpop'] !== "" && $post['maxlists'] !== "" && $post['maxsql'] !== "" && $post['maxsub'] !== "" && $post['maxpark'] !== "" && $post['maxaddon'] !== "" && $post['MAX_EMAIL_PER_HOUR'] !== "" && $post['MAX_DEFER_FAIL_PERCENTAGE'] !== "") {
                    Loader::loadModels($this, array("Services"));
                    $add_new = $reseller->addpkg($post);
                    $this->log($module_row->meta->hostname . "|Create New Package By a Reseller {$service_fields->username}", serialize("addpkg"), "input", true);
                    echo "<div class='alert alert-success alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>" . Language::_("tastycpanel.success", true) . "</p>
			</div><script>$(document).ready(function (e) { $('#global_modal').modal('hide');});</script>";
                } else {
                    $error = array(
                        0 => array(
                            "result" => Language::_("tastycpanel.empty_invalid_values", true)
                        )
                    );
                    echo "<div class='alert alert-danger alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>{$error[0]['result']}</p>
			</div>";
                }
            } else {
                $customfiles = $this->customStyleJS();
                $package_list = $reseller->listpkgs();
                $pkglist = "";
                if (is_array($package_list)) {
                    foreach ($package_list as $key => $value) {
                        $pkglist .= "<option value='{$package_list[$key]->name}'>{$package_list[$key]->name}</option>";
                    }
                }

                $this->Form->create("", array('onsubmit' => 'return false', 'id' => 'addform', 'autocomplete' => "off"));
                echo" {$customfiles}
            <div class='modal-body'>
<div class='div_response'></div>";
                echo'<div class="form-group">
   <label>' . Language::_("tastycpanel.listaccounts.pkgname", true) . '</label>
    <input type="text" class="form-control" value="" id="name" name="name" placeholder=""></div>
<div class="form-group">
   <label>' . Language::_("tastycpanel.listaccounts.quota", true) . ' (MB)</label>
    <input type="text" class="form-control" value="" id="quota" name="quota" placeholder="e.g: 250 or unlimited"></div>
<div class="form-group">
   <label>' . Language::_("tastycpanel.listaccounts.bwlimit", true) . ' (MB)</label>
    <input type="text" class="form-control" value="" id="bwlimit" name="bwlimit" placeholder="e.g: 250 or unlimited">
</div>
<div class="form-group">
   <label>' . Language::_("tastycpanel.listaccounts.maxftp", true) . '</label>
    <input type="text" class="form-control" value="" id="maxftp" name="maxftp" placeholder="e.g: 5 or unlimited">
</div>
<div class="form-group">
   <label>' . Language::_("tastycpanel.listaccounts.maxemail", true) . '</label>
    <input type="text" class="form-control" value="" id="maxpop" name="maxpop" placeholder="e.g: 5 or unlimited">
</div>
<div class="form-group">
   <label>' . Language::_("tastycpanel.listaccounts.maxemaillist", true) . '</label>
    <input type="text" class="form-control" value="" id="maxlists" name="maxlists" placeholder="e.g: 5 or unlimited">
</div>
<div class="form-group">
   <label>' . Language::_("tastycpanel.listaccounts.maxdb", true) . '</label>
    <input type="text" class="form-control" value="" id="maxsql" name="maxsql" placeholder="e.g: 5 or unlimited">
</div>
<div class="form-group">
   <label>' . Language::_("tastycpanel.listaccounts.maxsubdomains", true) . '</label>
    <input type="text" class="form-control" value="" id="maxsub" name="maxsub" placeholder="e.g: 5 or unlimited">
</div>
<div class="form-group">
   <label>' . Language::_("tastycpanel.listaccounts.maxparkeddomains", true) . '</label>
    <input type="text" class="form-control" value="" id="maxpark" name="maxpark" placeholder="e.g: 5 or unlimited">
</div>
<div class="form-group">
   <label>' . Language::_("tastycpanel.listaccounts.maxaddondomains", true) . '</label>
    <input type="text" class="form-control" value="" id="maxaddon" name="maxaddon" placeholder="e.g: 5 or unlimited">
</div>
<div class="form-group">
   <label>' . Language::_("tastycpanel.listaccounts.maxhourlyemail", true) . '</label>
    <input type="text" class="form-control" value="" id="MAX_EMAIL_PER_HOUR" name="MAX_EMAIL_PER_HOUR" placeholder="">
</div>
<div class="form-group">
   <label>' . Language::_("tastycpanel.listaccounts.maxpercent", true) . '</label>
    <input type="text" class="form-control" value="unlimited" id="MAX_DEFER_FAIL_PERCENTAGE" name="MAX_DEFER_FAIL_PERCENTAGE" placeholder="">
</div>
</div>
<div class="modal-footer">
<button type="button" name="cancel" class="btn btn-default" data-dismiss="modal"><i class="fa fa-ban"></i> ' . Language::_("tastycpanel.cancel", true) . '</button>
<button type="button" class="btn btn-primary" name="add_new" id="addnewsubmit"><i class="fa fa-plus-circle"></i> ' . Language::_("tastycpanel.submit", true) . '</button>
</div>
<script type="text/javascript">
    $(document).ready(function() {
        $("#addnewsubmit").click(function () {
    var form = $("#addform").serialize();
    doAjaxPost("' . $this->base_uri . "services/manage/" . $service->id . "/listaccounts/addpkg/?" . '"+ form, form);
        });
    });
</script>
    ';
                $this->Form->end();
            }

            exit();
        }
    }

    public function listaccountscreate($package, $service, array $get = null, array $post = null, array $files = null) {

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            Loader::loadHelpers($this, array("Form", "Html"));
            $service_fields = $this->serviceFieldsToObject($service->fields);
            $module_row = $this->getModuleRow($package->module_row);
            $cpanel = $this->getcPanelApi($module_row->meta->accesskey, $module_row->meta->hostname, $module_row->meta->username, $module_row->meta->use_ssl, false);
            $access_key = $cpanel->getResellerAccessKey(array('host' => $module_row->meta->hostname, 'username' => $service_fields->username, 'password' => $service_fields->password));
            $reseller = $this->getcPanelApi($access_key, $module_row->meta->hostname, $service_fields->username, $module_row->meta->use_ssl, false);
            if (isset($post) && !empty($post)) {
                if ($post['username'] !== "" && $post['domain'] !== "" && $post['password'] !== "" && $post['plan'] !== "" && $post['contactemail'] !== "") {
                    Loader::loadModels($this, array("Services"));
                    $createaccount = $reseller->createacct($post);
                    if (isset($createaccount->metadata->result) && $createaccount->metadata->result !== 0) {
                        $this->log($module_row->meta->hostname . "|Create New Account By a Reseller {$service_fields->username}", serialize("createacct"), "input", true);
                        echo "<div class='alert alert-success alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>" . Language::_("tastycpanel.success", true) . "</p>
			</div><script>$(document).ready(function (e) { $('#global_modal').modal('hide');});</script>";
                    } else if (isset($createaccount->metadata->result) && $createaccount->metadata->result === 0) {
                        $error = array(
                            0 => array(
                                "result" => $createaccount->metadata->reason
                            )
                        );
                        echo "<div class='alert alert-danger alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>{$error[0]['result']}</p>
			</div>";
                    }
                } else {
                    $error = array(
                        0 => array(
                            "result" => Language::_("tastycpanel.empty_invalid_values", true)
                        )
                    );
                    echo "<div class='alert alert-danger alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>{$error[0]['result']}</p>
			</div>";
                }
            } else {
                $customfiles = $this->customStyleJS();
                $package_list = $reseller->listpkgs();
                $pkglist = "";
                if (is_array($package_list)) {
                    foreach ($package_list as $key => $value) {
                        $pkglist .= "<option value='{$package_list[$key]->name}'>{$package_list[$key]->name}</option>";
                    }
                }

                $this->Form->create("", array('onsubmit' => 'return false', 'id' => 'addform', 'autocomplete' => "off"));
                echo" {$customfiles}
            <div class='modal-body'>
<div class='div_response'></div>";
                echo'<div class="form-group">
   <label>' . Language::_("tastycpanel.username", true) . '</label>
    <input type="text" class="form-control" value="" id="username" name="username" placeholder=""></div>
<div class="form-group">
   <label>' . Language::_("tastycpanel.domain", true) . '</label>
    <input type="text" class="form-control" value="" id="domain" name="domain" placeholder=""></div>
<div class="form-group">
   <label>' . Language::_("tastycpanel.listaccounts.password", true) . '</label>
    <input type="password" class="form-control" value="" id="password" name="password" placeholder="**********">
</div>
<div class="new_div"></div>
<div class="form-group">
   <label>' . Language::_("tastycpanel.listaccounts.package", true) . '</label>
<select name="plan" id="plan" class="form-control">
' . $pkglist . '
</select>
</div>
<div class="form-group">
   <label>' . Language::_("tastycpanel.email.email_address", true) . '</label>
    <input type="email" class="form-control" value="" id="contactemail" name="contactemail" placeholder=""></div>
</div>
<div class="modal-footer">
<button type="button" name="cancel" class="btn btn-default" data-dismiss="modal"><i class="fa fa-ban"></i> ' . Language::_("tastycpanel.cancel", true) . '</button>
<button type="button" class="btn btn-default" id="generate"><i class="fa fa-key"></i> ' . Language::_("tastycpanel.service.generate", true) . '</button>
<button type="button" class="btn btn-primary" name="add_new" id="addnewsubmit"><i class="fa fa-plus-circle"></i> ' . Language::_("tastycpanel.submit", true) . '</button>
</div>
<script type="text/javascript">
    $(document).ready(function() {
        $("#addnewsubmit").click(function () {
    var form = $("#addform").serialize();
    doAjaxPost("' . $this->base_uri . "services/manage/" . $service->id . "/listaccounts/create/?" . '"+ form, form);
        });
        $("#generate").click(function () {
            doAjaxGet("' . $this->base_uri . "services/manage/" . $service->id . "/changepassword/generatepass/" . '", "' . Language::_("tastycpanel.generated_password", true) . '");
        });
    });
</script>
    ';
                $this->Form->end();
            }

            exit();
        }
    }

    public function listaccountschangepassword($package, $service, array $get = null, array $post = null, array $files = null) {

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            if (isset($get["user"])) {
                Loader::loadHelpers($this, array("Form", "Html"));
                $service_fields = $this->serviceFieldsToObject($service->fields);
                $module_row = $this->getModuleRow($package->module_row);
                $cpanel = $this->getcPanelApi($module_row->meta->accesskey, $module_row->meta->hostname, $module_row->meta->username, $module_row->meta->use_ssl, false);
                $access_key = $cpanel->getResellerAccessKey(array('host' => $module_row->meta->hostname, 'username' => $service_fields->username, 'password' => $service_fields->password));
                $reseller = $this->getcPanelApi($access_key, $module_row->meta->hostname, $service_fields->username, $module_row->meta->use_ssl, false);
                if (isset($post) && !empty($post)) {
                    if ($post['user'] !== "" && $post['password'] !== "") {
                        Loader::loadModels($this, array("Services"));
                        $change_password = $reseller->passwd($post['user'], $post['password']);
                        $this->log($module_row->meta->hostname . "|Change Reseller {$service_fields->username} Child Account Password", serialize("passwd"), "input", true);
                        if (isset($change_password->result) && $change_password->result == 0 && isset($change_password->reason)) {
                            $error = array(
                                0 => array(
                                    "result" => $change_password->reason
                                )
                            );
                            echo "<div class='alert alert-danger alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>{$error[0]['result']}</p>
			</div>";
                        } else {
                            echo "<div class='alert alert-success alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>" . Language::_("tastycpanel.success", true) . "</p>
			</div><script>$(document).ready(function (e) { $('#global_modal').modal('hide');});</script>";
                        }
                    } else {
                        $error = array(
                            0 => array(
                                "result" => Language::_("tastycpanel.empty_invalid_values", true)
                            )
                        );
                        echo "<div class='alert alert-danger alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>{$error[0]['result']}</p>
			</div>";
                    }
                } else {
                    $customfiles = $this->customStyleJS();

                    $this->Form->create("", array('onsubmit' => 'return false', 'id' => 'changepassform', 'autocomplete' => "off"));
                    echo" {$customfiles}
            <div class='modal-body'>
<div class='div_response'></div>";
                    $this->Form->fieldHidden("user", $this->Html->ifSet($get["user"]), array('id' => "user"));

                    echo'<div class="form-group">
   <label>' . Language::_("tastycpanel.changepassword", true) . " " . Language::_("tastycpanel.for", true) . " " . $get["user"] . '</label>
    <input type="password" class="form-control" value="" id="password" name="password" placeholder="**********">
</div>
<div class="new_div"></div>
</div>
<div class="modal-footer">
<button type="button" name="cancel" class="btn btn-default" data-dismiss="modal"><i class="fa fa-ban"></i> ' . Language::_("tastycpanel.cancel", true) . '</button>
<button type="button" class="btn btn-default" id="generate"><i class="fa fa-key"></i> ' . Language::_("tastycpanel.service.generate", true) . '</button>
<button type="button" class="btn btn-primary" name="change_password" id="change_password"><i class="fa fa-edit"></i> ' . Language::_("tastycpanel.submit", true) . '</button>
</div>
<script type="text/javascript">
    $(document).ready(function () {
        $("#change_password").click(function () {
    var form = $("#changepassform").serialize();
    doAjaxPost("' . $this->base_uri . "services/manage/" . $service->id . "/listaccounts/changepassword/?" . '"+ form, form);
        });
        $("#generate").click(function () {
            doAjaxGet("' . $this->base_uri . "services/manage/" . $service->id . "/changepassword/generatepass/" . '", "' . Language::_("tastycpanel.generated_password", true) . '");
        });
    });
</script>


    ';
                    $this->Form->end();
                }

                exit();
            } else {
                return false;
            }
        }
    }

    public function listaccountsmodify($package, $service, array $get = null, array $post = null, array $files = null) {

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            if (isset($get["user"])) {
                Loader::loadHelpers($this, array("Form", "Html"));
                $service_fields = $this->serviceFieldsToObject($service->fields);
                $module_row = $this->getModuleRow($package->module_row);
                $cpanel = $this->getcPanelApi($module_row->meta->accesskey, $module_row->meta->hostname, $module_row->meta->username, $module_row->meta->use_ssl, false);
                $access_key = $cpanel->getResellerAccessKey(array('host' => $module_row->meta->hostname, 'username' => $service_fields->username, 'password' => $service_fields->password));
                $reseller = $this->getcPanelApi($access_key, $module_row->meta->hostname, $service_fields->username, $module_row->meta->use_ssl, false);
                $accountInfo = $reseller->accountsummary($get["user"]);
                if (isset($post) && !empty($post)) {
                    if ($post['username'] !== "" && $post['domain'] !== "" && $post['plan'] !== "" && $post['contactemail'] !== "") {
                        Loader::loadModels($this, array("Services"));
                        $modify_account = $reseller->modifyacct($get['user'], $post);
                        if (isset($modify_account->result) && $modify_account->result !== 0) {
                            $this->log($module_row->meta->hostname . "|Modify Account By a Reseller {$service_fields->username}", serialize("modifyacct"), "input", true);
                            echo "<div class='alert alert-success alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>" . Language::_("tastycpanel.success", true) . "</p>
			</div><script>$(document).ready(function (e) { $('#global_modal').modal('hide');});</script>";
                        } else if (isset($modify_account->result) && $modify_account->result == 0 && isset($modify_account->reason)) {
                            $error = array(
                                0 => array(
                                    "result" => $modify_account->reason
                                )
                            );
                            echo "<div class='alert alert-danger alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>{$error[0]['result']}</p>
			</div>";
                        }
                    } else {
                        $error = array(
                            0 => array(
                                "result" => Language::_("tastycpanel.empty_invalid_values", true)
                            )
                        );
                        echo "<div class='alert alert-danger alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>{$error[0]['result']}</p>
			</div>";
                    }
                } else {
                    $customfiles = $this->customStyleJS();
                    $package_list = $reseller->listpkgs();
                    $pkglist = "";
                    if (is_array($package_list)) {
                        foreach ($package_list as $key => $value) {
                            if ($package_list[$key]->name === $accountInfo->plan) {
                                $pkglist .= "<option value='{$package_list[$key]->name}' selected='selected'>{$package_list[$key]->name}</option>";
                            } else {
                                $pkglist .= "<option value='{$package_list[$key]->name}'>{$package_list[$key]->name}</option>";
                            }
                        }
                    }

                    $this->Form->create("", array('onsubmit' => 'return false', 'id' => 'modifyform', 'autocomplete' => "off"));
                    $this->Form->fieldHidden("user", $this->Html->ifSet($get["user"]), array('id' => "user"));
                    echo" {$customfiles}
            <div class='modal-body'>
<div class='div_response'></div>";
                    echo'<div class="form-group">
   <label>' . Language::_("tastycpanel.username", true) . '(Current Username Is: ' . $get["user"] . ')</label>
    <input type="text" class="form-control" value="' . $accountInfo->user . '" id="username" name="username" placeholder="Current Username Is: ' . $get["user"] . '"></div>
<div class="form-group">
   <label>' . Language::_("tastycpanel.domain", true) . '</label>
    <input type="text" class="form-control" value="' . $accountInfo->domain . '" id="domain" name="domain" placeholder=""></div>
<div class="form-group">
   <label>' . Language::_("tastycpanel.listaccounts.package", true) . '</label>
<select name="plan" id="plan" class="form-control">
' . $pkglist . '
</select>
</div>
<div class="form-group">
   <label>' . Language::_("tastycpanel.email.email_address", true) . '</label>
    <input type="email" class="form-control" value="" id="contactemail" name="contactemail" placeholder=""></div>
</div>
<div class="modal-footer">
<button type="button" name="cancel" class="btn btn-default" data-dismiss="modal"><i class="fa fa-ban"></i> ' . Language::_("tastycpanel.cancel", true) . '</button>
<button type="button" class="btn btn-primary" name="modifysubmit" id="addnewsubmit"><i class="fa fa-plus-circle"></i> ' . Language::_("tastycpanel.submit", true) . '</button>
</div>
<script type="text/javascript">
    $(document).ready(function() {
        $("#modifysubmit").click(function () {
    var form = $("#modifyform").serialize();
    doAjaxPost("' . $this->base_uri . "services/manage/" . $service->id . "/listaccounts/modify/?" . '"+ form, form);
        });
    });
</script>
    ';
                    $this->Form->end();
                }

                exit();
            } else {
                return false;
            }
        }
    }

    public function listaccountsterminate($package, $service, array $get = null, array $post = null, array $files = null) {

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            if (isset($get["user"])) {
                Loader::loadHelpers($this, array("Form", "Html"));
                $service_fields = $this->serviceFieldsToObject($service->fields);
                $module_row = $this->getModuleRow($package->module_row);
                $cpanel = $this->getcPanelApi($module_row->meta->accesskey, $module_row->meta->hostname, $module_row->meta->username, $module_row->meta->use_ssl, false);
                $access_key = $cpanel->getResellerAccessKey(array('host' => $module_row->meta->hostname, 'username' => $service_fields->username, 'password' => $service_fields->password));
                $reseller = $this->getcPanelApi($access_key, $module_row->meta->hostname, $service_fields->username, $module_row->meta->use_ssl, false);
                if (isset($post) && !empty($post)) {
                    if ($post['user'] !== "" && $post['confirm'] !== "I understand this will irrevocably remove all the accounts that have been checked") {
                        Loader::loadModels($this, array("Services"));
                        $removeacct = $reseller->removeacct($post);
                        $this->log($module_row->meta->hostname . "|Terminate Reseller {$service_fields->username} Child Account Account", serialize("removeacct"), "input", true);
                        echo "<div class='alert alert-success alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>" . Language::_("tastycpanel.success", true) . "</p>
			</div><script>$(document).ready(function (e) { $('#global_modal').modal('hide');});</script>";
                    } else {
                        $error = array(
                            0 => array(
                                "result" => Language::_("tastycpanel.empty_invalid_values", true)
                            )
                        );
                        echo "<div class='alert alert-danger alert-dismissable'>
		<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button>
				<p>{$error[0]['result']}</p>
			</div>";
                    }
                } else {
                    $customfiles = $this->customStyleJS();

                    $this->Form->create("", array('onsubmit' => 'return false', 'id' => 'terminateform', 'autocomplete' => "off"));
                    echo" {$customfiles}
            <div class='modal-body'>
<div class='div_response'></div>";
                    $this->Form->fieldHidden("user", $this->Html->ifSet($get["user"]), array('id' => "user"));

                    echo'<div class="form-group">
   <label>' . Language::_("tastycpanel.confirmterminate", true) . " " . $get["user"] . '</label>
    <input type="text" class="form-control" value="" id="confirm" name="confirm" placeholder="">
</div>
<div class="new_div"></div>
</div>
<div class="modal-footer">
<button type="button" name="cancel" class="btn btn-default" data-dismiss="modal"><i class="fa fa-ban"></i> ' . Language::_("tastycpanel.cancel", true) . '</button>
<button type="button" class="btn btn-primary" name="change_password" id="terminate_acc"><i class="fa fa-edit"></i> ' . Language::_("tastycpanel.submit", true) . '</button>
</div>
<script type="text/javascript">
    $(document).ready(function () {
        $("#terminate_acc").click(function () {
    var form = $("#terminateform").serialize();
    doAjaxPost("' . $this->base_uri . "services/manage/" . $service->id . "/listaccounts/terminate/?" . '"+ form, form);
        });
    });
</script>


    ';
                    $this->Form->end();
                }

                exit();
            } else {
                return false;
            }
        }
    }

    public function listaccounts($package, $service, array $get = null, array $post = null, array $files = null) {
        return null;
//        if ($package->meta->type === "reseller") {
//            if (isset($get[2])) {
//                if ($get[2] === "create") {
//                    return $this->listaccountscreate($package, $service, $get, $post, $files);
//                } else if ($get[2] === "terminate") {
//                    return $this->listaccountsterminate($package, $service, $get, $post, $files);
//                } else if ($get[2] === "changepassword") {
//                    return $this->listaccountschangepassword($package, $service, $get, $post, $files);
//                } else if ($get[2] === "modify") {
//                    return $this->listaccountsmodify($package, $service, $get, $post, $files);
//                } else if ($get[2] === "addpkg") {
//                    return $this->listaccountsaddpkg($package, $service, $get, $post, $files);
//                } else if ($get[2] === "editpkg") {
//                    return $this->listaccountseditpkg($package, $service, $get, $post, $files);
//                }
//            }
//            return $this->listaccountsmain($package, $service, $get, $post, $files);
//        }
    }

    public function manageapps($package, $service, array $get = null, array $post = null, array $files = null) {
        if ($package->meta->manageapps !== "false") {
            if (isset($get[2])) {
                if ($get[2] === "installapps") {
                    if ($package->meta->manageapps === "softaculous") {
                        return $this->manageappsinstall($package, $service, $get, $post, $files);
                    } else if ($package->meta->manageapps === "installatron") {
                        return $this->manageappsinstallinstallatron($package, $service, $get, $post, $files);
                    }
                }
            } else {
                if ($package->meta->manageapps === "softaculous") {
                    return $this->manageappsmain($package, $service, $get, $post, $files);
                } else if ($package->meta->manageapps === "installatron") {
                    return $this->manageappsmaininstallatron($package, $service, $get, $post, $files);
                }
            }
        }
    }

    public function databases($package, $service, array $get = null, array $post = null, array $files = null) {
        if ($package->meta->databases === "true") {
            if (isset($get[2])) {
                if ($get[2] === "addnewdb") {
                    return $this->databasesaddnewdb($package, $service, $get, $post, $files);
                } else if ($get[2] === "addnewdbuser") {
                    return $this->databasesaddnewdbuser($package, $service, $get, $post, $files);
                } else if ($get[2] === "addnewremote") {
                    return $this->databasesaddnewremote($package, $service, $get, $post, $files);
                } else if ($get[2] === "changepass") {
                    return $this->databaseschangepass($package, $service, $get, $post, $files);
                } else if ($get[2] === "addusertodb") {
                    return $this->databasesaddusertodb($package, $service, $get, $post, $files);
                }
            } else {
                return $this->databasesmain($package, $service, $get, $post, $files);
            }
        }
    }

    public function backups($package, $service, array $get = null, array $post = null, array $files = null) {
        if ($package->meta->backups === "true") {
            if (isset($get[2])) {
                if ($get[2] === "addnew") {
                    return $this->backupsaddnew($package, $service, $get, $post, $files);
                }
            } else {
                return $this->backupsmain($package, $service, $get, $post, $files);
            }
        }
    }

    public function ipblocker($package, $service, array $get = null, array $post = null, array $files = null) {
        if ($package->meta->ipblocker === "true") {
            if (isset($get[2])) {
                if ($get[2] === "addnew") {
                    return $this->ipblockeraddnew($package, $service, $get, $post, $files);
                }
            } else {
                return $this->ipblockermain($package, $service, $get, $post, $files);
            }
        }
    }

    public function cronjobs($package, $service, array $get = null, array $post = null, array $files = null) {
        if ($package->meta->cronjobs === "true") {
            if (isset($get[2])) {
                if ($get[2] === "addnew") {
                    return $this->cronjobsaddnew($package, $service, $get, $post, $files);
                }
            } else {
                return $this->cronjobsmain($package, $service, $get, $post, $files);
            }
        }
    }

    public function parkeddomains($package, $service, array $get = null, array $post = null, array $files = null) {
        if ($package->meta->parkeddomains === "true") {
            if (isset($get[2])) {
                if ($get[2] === "addnew") {
                    return $this->parkeddomainsaddnew($package, $service, $get, $post, $files);
                }
            } else {
                return $this->parkeddomainsmain($package, $service, $get, $post, $files);
            }
        }
    }

    public function addondomains($package, $service, array $get = null, array $post = null, array $files = null) {
        if ($package->meta->addondomains === "true") {
            if (isset($get[2])) {
                if ($get[2] === "addnew") {
                    return $this->addondomainsaddnew($package, $service, $get, $post, $files);
                }
            } else {
                return $this->addondomainsmain($package, $service, $get, $post, $files);
            }
        }
    }

    public function subdomains($package, $service, array $get = null, array $post = null, array $files = null) {
        if ($package->meta->subdomains === "true") {
            if (isset($get[2])) {
                if ($get[2] === "addnew") {
                    return $this->subdomainsaddnew($package, $service, $get, $post, $files);
                }
            } else {
                return $this->subdomainsmain($package, $service, $get, $post, $files);
            }
        }
    }

    public function ftpaccounts($package, $service, array $get = null, array $post = null, array $files = null) {
        if ($package->meta->ftpaccounts === "true") {
            if (isset($get[2])) {
                if ($get[2] === "changequota") {
                    return $this->ftpchangequota($package, $service, $get, $post, $files);
                } else if ($get[2] === "changepassword") {
                    return $this->ftpchangepassword($package, $service, $get, $post, $files);
                } else if ($get[2] === "addnew") {
                    return $this->ftpaddnew($package, $service, $get, $post, $files);
                }
            } else {
                return $this->ftpmain($package, $service, $get, $post, $files);
            }
        }
    }

    public function emailforwarders($package, $service, array $get = null, array $post = null, array $files = null) {
        if ($package->meta->emailforwarders === "true") {
            if (isset($get[2])) {
                if ($get[2] === "addnew") {
                    return $this->emailforwardersaddnew($package, $service, $get, $post, $files);
                } else if ($get[2] === "addnewdomainforward") {
                    return $this->emaildomainforwardersaddnew($package, $service, $get, $post, $files);
                }
            } else {
                return $this->emailforwardersmain($package, $service, $get, $post, $files);
            }
        }
    }

    public function email($package, $service, array $get = null, array $post = null, array $files = null) {
        if ($package->meta->email === "true") {
            if (isset($get[2])) {
                if ($get[2] === "changequota") {
                    return $this->emailchangequota($package, $service, $get, $post, $files);
                } else if ($get[2] === "changepassword") {
                    return $this->emailchangepassword($package, $service, $get, $post, $files);
                } else if ($get[2] === "addnew") {
                    return $this->emailaddnew($package, $service, $get, $post, $files);
                }
            } else {
                return $this->emailmain($package, $service, $get, $post, $files);
            }
        }
    }

    public function firewall($package, $service, array $get = null, array $post = null, array $files = null) {
        if ($package->meta->firewall !== "false") {
            $client_ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
            $customfiles = $this->customStyleJS();
            $this->view = new View("firewall", "default");
            $this->view->base_uri = $this->base_uri;
            Loader::loadHelpers($this, array("Form", "Html"));
            $service_fields = $this->serviceFieldsToObject($service->fields);
            $module_row = $this->getModuleRow($package->module_row);
            $cpanel = $this->getcPanelApi($module_row->meta->accesskey, $module_row->meta->hostname, $module_row->meta->username, $module_row->meta->use_ssl, false);
            if (strtolower($module_row->meta->username) == "root") {
                $action = "kill";
            } else {
                $action = "qkill";
            }

            if (isset($post['unblock'])) {
                if (isset($post['ip'])) {
                    if (!empty($post['ip'])) {
                        $cpanel->firewallQuery(array('action' => $action, 'ip' => $post['ip']));
                    } else {
                        $error = array(
                            0 => array(
                                "result" => Language::_("tastycpanel.empty_invalid_values", true)
                            )
                        );
                        $this->Input->setErrors($error[0]);
                    }
                } else {
                    $cpanel->firewallQuery(array('action' => $action, 'ip' => $client_ip));
                }
            }
            $this->view->set("name_servers", $module_row->meta->name_servers);
            $this->view->set("module_row", $module_row);
            $this->view->set("post", $post);
            $this->view->set("service_fields", $service_fields);
            $this->view->set("client_ip", $client_ip);
            $this->view->set("type", $package->meta->type);
            $this->view->set("showIpInput", $package->meta->firewall_ip);
            $this->view->set("service_id", $service->id);

            $this->view->setDefaultView("components" . DS . "modules" . DS . "tastycpanelmodule" . DS);
            return $customfiles . $this->view->fetch();
        }
    }

    public function accountusage($package, $service, array $get = null, array $post = null, array $files = null) {
        if ($package->meta->accountusage !== "false") {
            $customfiles = $this->customStyleJS();
            $this->view = new View("account_usage", "default");
            $this->view->base_uri = $this->base_uri;
            Loader::loadHelpers($this, array("Form", "Html"));
            $service_fields = $this->serviceFieldsToObject($service->fields);
            $module_row = $this->getModuleRow($package->module_row);
            $cpanel = $this->getcPanelApi($module_row->meta->accesskey, $module_row->meta->hostname, $module_row->meta->username, $module_row->meta->use_ssl, false);
            $stats = $cpanel->api2_query($service_fields->username, "StatsBar", "stat", array("display" => "hostname|dedicatedip|sharedip|hostingpackage|operatingsystem|cpanelversion|phpversion|diskusage|bandwidthusage|ftpaccounts|emailaccounts|sqldatabases|parkeddomains|addondomains|subdomains"));

            $user_stats = array();
            foreach ($stats->cpanelresult->data as $key => $value) {
                $user_stats[$stats->cpanelresult->data[$key]->name] = array(
                    "name" => isset($stats->cpanelresult->data[$key]->item) ? $stats->cpanelresult->data[$key]->item : null,
                    "max" => isset($stats->cpanelresult->data[$key]->max) ? $stats->cpanelresult->data[$key]->max : null,
                    "count" => isset($stats->cpanelresult->data[$key]->count) ? $stats->cpanelresult->data[$key]->count : null,
                    "value" => isset($stats->cpanelresult->data[$key]->value) ? $stats->cpanelresult->data[$key]->value : null,
                    "percent" => isset($stats->cpanelresult->data[$key]->percent) ? $stats->cpanelresult->data[$key]->percent : null,
                    "units" => isset($stats->cpanelresult->data[$key]->units) ? $stats->cpanelresult->data[$key]->units : null,
                );
                if (isset($stats->cpanelresult->data[$key]->percent) && $stats->cpanelresult->data[$key]->percent >= 75) {
                    $user_stats[$stats->cpanelresult->data[$key]->name]['class'] = "progress-bar-danger";
                } else if (isset($stats->cpanelresult->data[$key]->percent) && $stats->cpanelresult->data[$key]->percent >= 50) {
                    $user_stats[$stats->cpanelresult->data[$key]->name]['class'] = "progress-bar-warning";
                } else {
                    $user_stats[$stats->cpanelresult->data[$key]->name]['class'] = "";
                }
            }
            if (isset($post['whostmgrd'])) {
                $login_array = array('user' => $service_fields->username, 'service' => 'whostmgrd');
                $login = $cpanel->apiQuery("create_user_session", $login_array);
                header("Location: {$login->data->url}");
                exit();
            } else if (isset($post['cpaneld'])) {
                $login_array = array('user' => $service_fields->username, 'service' => 'cpaneld');
                if (!empty($post['cpaneld'])) {
                    $login_array['app'] = $post['cpaneld'];
                }
                $login = $cpanel->apiQuery("create_user_session", $login_array);
                header("Location: {$login->data->url}");
                exit();
            } else if (isset($post['webmaild'])) {
                $login_array = array('user' => $service_fields->username, 'service' => 'webmaild');
                $login = $cpanel->apiQuery("create_user_session", $login_array);
                header("Location: {$login->data->url}");
                exit();
            }

            $this->view->set("name_servers", $module_row->meta->name_servers);
            $this->view->set("module_row", $module_row);
            $this->view->set("service_fields", $service_fields);
            $this->view->set("stats", $user_stats);
            $this->view->set("type", $package->meta->type);
            $this->view->set("service_id", $service->id);

            $this->view->setDefaultView("components" . DS . "modules" . DS . "tastycpanelmodule" . DS);
            return $customfiles . $this->view->fetch();
        }
    }

    public function admin_accountusage($package, $service, array $get = null, array $post = null, array $files = null) {
        $customfiles = $this->customStyleJS();
        $this->view = new View("admin_accountusage", "default");
        $this->view->base_uri = $this->base_uri;
        Loader::loadHelpers($this, array("Form", "Html"));
        $service_fields = $this->serviceFieldsToObject($service->fields);
        $module_row = $this->getModuleRow($package->module_row);
        $cpanel = $this->getcPanelApi($module_row->meta->accesskey, $module_row->meta->hostname, $module_row->meta->username, $module_row->meta->use_ssl, false);
        $stats = $cpanel->api2_query($service_fields->username, "StatsBar", "stat", array("display" => "hostname|dedicatedip|sharedip|hostingpackage|operatingsystem|cpanelversion|phpversion|diskusage|bandwidthusage|ftpaccounts|emailaccounts|sqldatabases|parkeddomains|addondomains|subdomains"));

        $user_stats = array();
        foreach ($stats->cpanelresult->data as $key => $value) {
            $user_stats[$stats->cpanelresult->data[$key]->name] = array(
                "name" => isset($stats->cpanelresult->data[$key]->item) ? $stats->cpanelresult->data[$key]->item : null,
                "max" => isset($stats->cpanelresult->data[$key]->max) ? $stats->cpanelresult->data[$key]->max : null,
                "count" => isset($stats->cpanelresult->data[$key]->count) ? $stats->cpanelresult->data[$key]->count : null,
                "value" => isset($stats->cpanelresult->data[$key]->value) ? $stats->cpanelresult->data[$key]->value : null,
                "percent" => isset($stats->cpanelresult->data[$key]->percent) ? $stats->cpanelresult->data[$key]->percent : null,
                "units" => isset($stats->cpanelresult->data[$key]->units) ? $stats->cpanelresult->data[$key]->units : null,
            );
            if (isset($stats->cpanelresult->data[$key]->percent) && $stats->cpanelresult->data[$key]->percent >= 75) {
                $user_stats[$stats->cpanelresult->data[$key]->name]['class'] = "progress-bar-danger";
            } else if (isset($stats->cpanelresult->data[$key]->percent) && $stats->cpanelresult->data[$key]->percent >= 50) {
                $user_stats[$stats->cpanelresult->data[$key]->name]['class'] = "progress-bar-warning";
            } else {
                $user_stats[$stats->cpanelresult->data[$key]->name]['class'] = "";
            }
        }
        $this->view->set("module_row", $module_row);
        $this->view->set("service_fields", $service_fields);
        $this->view->set("stats", $user_stats);
        $this->view->set("type", $package->meta->type);
        $this->view->set("service_id", $service->id);

        $this->view->setDefaultView("components" . DS . "modules" . DS . "tastycpanelmodule" . DS);
        return $customfiles . $this->view->fetch();
    }

    public function changepassword($package, $service, array $get = null, array $post = null, array $files = null) {
        if (isset($get[2]) && $get[2] === "generatepass") {
            return $this->generatepass($package, $service, $get, $post, $files);
        } else {
            if ($package->meta->changepassword !== "false") {
                $customfiles = $this->customStyleJS();
                $this->view = new View("change_password", "default");
                $this->view->setDefaultView("components" . DS . "modules" . DS . "tastycpanelmodule" . DS);
                $this->view->base_uri = $this->base_uri;
                $service_fields = $this->serviceFieldsToObject($service->fields);
                $module_row = $this->getModuleRow($package->module_row);
                $cpanel = $this->getcPanelApi($module_row->meta->accesskey, $module_row->meta->hostname, $module_row->meta->username, $module_row->meta->use_ssl, false);
                // Load the helpers required for this view
                Loader::loadHelpers($this, array("Form", "Html"));

                $service_fields = $this->serviceFieldsToObject($service->fields);
                if (isset($post['changepassword'])) {
                    if ($post['password'] !== "" && $post['confirmpassword'] !== "") {
                        if ($post['password'] != $post['confirmpassword']) {
                            $error = array(
                                0 => array(
                                    "result" => Language::_("tastycpanel.error.passwordemptymatch", true)
                                )
                            );
                            $this->Input->setErrors($error);
                        } else {
                            Loader::loadModels($this, array("Services"));
                            $data = array(
                                'password' => $this->Html->ifSet($post['password']),
                                'domain_name' => $this->Html->ifSet($service_fields->domain_name),
                                'username' => $this->Html->ifSet($service_fields->username),
                                'custom_quota' => $this->Html->ifSet($service_fields->custom_quota),
                                'custom_bwlimit' => $this->Html->ifSet($service_fields->custom_bwlimit),
                                'dedicated_ip' => $this->Html->ifSet($service_fields->dedicated_ip)
                            );
                            $this->Services->edit($service->id, $data);

                            if ($this->Services->errors())
                                $this->Input->setErrors($this->Services->errors());

                            $this->log($module_row->meta->hostname . "- {$service_fields->username}|passwd", serialize("password_change"), "input", true);
                        }
                    }else {
                        $error = array(
                            0 => array(
                                "result" => Language::_("tastycpanel.error.passwordemptymatch", true)
                            )
                        );
                        $this->Input->setErrors($error);
                    }
                }
                $this->view->set("webdir", WEBDIR);
                $this->view->set("post", (object) $post);
                $this->view->set("service_fields", $service_fields);
                $this->view->set("service_id", $service->id);

                return $customfiles . $this->view->fetch();
            }
        }
    }

    public function moduleRowName() {
        return Language::_("tastycpanel.module_row", true);
    }

    public function moduleRowNamePlural() {
        return Language::_("tastycpanel.module_row_plural", true);
    }

    public function moduleGroupName() {
        return Language::_("tastycpanel.module_group", true);
    }

    public function moduleRowMetaKey() {
        return "hostname";
    }

    public function getGroupOrderOptions() {
        return array('first' => Language::_("tastycpanel.order_options.first", true));
    }

    public function selectModuleRow($module_group_id) {
        if (!isset($this->ModuleManager))
            Loader::loadModels($this, array("ModuleManager"));

        $group = $this->ModuleManager->getGroup($module_group_id);

        if ($group) {
            switch ($group->add_order) {
                default:
                case "first":

                    foreach ($group->rows as $row) {
                        return $row->id;
                    }

                    break;
            }
        }
        return 0;
    }

    private function getPkgs($module_row) {
        $packages = array();
        $whm = $this->getcPanelApi($module_row->meta->accesskey, $module_row->meta->hostname, $module_row->meta->username, $module_row->meta->use_ssl, false);
        $validate = $whm->listpkgs();
        if (is_array($validate)) {
            foreach ($validate as $key => $value) {
                $packages[$validate[$key]->name] = $validate[$key]->name;
            }
        }


        return $packages;
    }

    private function getAcls($module_row) {
        $packages = array();
        $whm = $this->getcPanelApi($module_row->meta->accesskey, $module_row->meta->hostname, $module_row->meta->username, $module_row->meta->use_ssl, false);
        $validate = $whm->listacls();
        if (is_array($validate)) {
            foreach ($validate as $key => $value) {
                $packages[$validate[$key]->name] = $validate[$key]->name;
            }
        }

        return $packages;
    }

    public function getPackageFields($vars = null) {
        Loader::loadHelpers($this, array("Html"));

        // Set any module fields
        $fields = new ModuleFields();
        $fields->setHtml("
            <script type=\"text/javascript\">
                $(document).ready(function() {
                    // Re-fetch module options
                    $('#type, #manageapps, #firewall').change(function() {
                        fetchModuleOptions();
                    });
                });
            </script>
        ");

        // Fetch all packages available for the given server or server group
        $module_row = null;
        if (isset($vars->module_group) && $vars->module_group == "") {
            // Set a module row if one is given
            if (isset($vars->module_row) && $vars->module_row > 0)
                $module_row = $this->getModuleRow($vars->module_row);
            else {
                // Set the first module row of any that exist
                $rows = $this->getModuleRows();
                if (isset($rows[0]))
                    $module_row = $rows[0];
                unset($rows);
            }
        }
        else {
            // Set the first module row from the list of servers in the selected group
            $rows = $this->getModuleRows($vars->module_group);
            if (isset($rows[0]))
                $module_row = $rows[0];
            unset($rows);
        }
        $packages = array();
        $acls = array();

        if ($module_row) {
            $packages = $this->getPkgs($module_row);
            $acls = array('' => Language::_("tastycpanel.package.acl_default", true)) + $this->getAcls($module_row);
        }
        $package = $fields->label(Language::_("tastycpanel.package", true), "package_name");
        $package->attach($fields->fieldSelect("meta[package]", $packages, $this->Html->ifSet($vars->meta['package']), array('id' => "package_name")));
        $fields->setField($package);

        if ($module_row && $module_row->meta->username == "root") {
            $select_options = array('standard' => "Standard", 'reseller' => "Reseller");
            $typefield = $fields->label(Language::_("tastycpanel.package.type", true), "type");
            $typefield->attach($fields->fieldSelect("meta[type]", $select_options, $this->Html->ifSet($vars->meta['type']), array('id' => "type")));
            $fields->setField($typefield);
        } else {
            $typefield = $fields->fieldHidden("meta[type]", "standard");
            $fields->setField($typefield);
        }
        if (isset($vars->meta['type']) && $vars->meta['type'] === "reseller") {
            $acl = $fields->label(Language::_("tastycpanel.package.acl", true), "acls");
            $acl->attach($fields->fieldSelect("meta[acls]", $acls, $this->Html->ifSet($vars->meta['acls']), array('id' => "acls")));
            $fields->setField($acl);
        }
        $select_options = array('true' => "Enable", 'false' => "Disable");
        $additionaldiskfield = $fields->label(Language::_("tastycpanel.additionaldisk", true), "additionaldisk");
        $additionaldiskfield->attach($fields->tooltip(Language::_("tastycpanel.additionaldisktip", true)));
        $additionaldiskfield->attach($fields->fieldSelect("meta[additionaldisk]", $select_options, $this->Html->ifSet($vars->meta['additionaldisk']), array('id' => "additionaldisk")));
        $fields->setField($additionaldiskfield);

        $additionalbandwidthfield = $fields->label(Language::_("tastycpanel.additionalbandwidth", true), "additionalbandwidth");
        $additionalbandwidthfield->attach($fields->tooltip(Language::_("tastycpanel.additionalbandwidthtip", true)));
        $additionalbandwidthfield->attach($fields->fieldSelect("meta[additionalbandwidth]", $select_options, $this->Html->ifSet($vars->meta['additionalbandwidth']), array('id' => "additionalbandwidth")));
        $fields->setField($additionalbandwidthfield);

        $accountusagefield = $fields->label(Language::_("tastycpanel.accountusage", true), "accountusage");
        $accountusagefield->attach($fields->fieldSelect("meta[accountusage]", $select_options, $this->Html->ifSet($vars->meta['accountusage']), array('id' => "accountusage")));
        $fields->setField($accountusagefield);

        $changepasswordfield = $fields->label(Language::_("tastycpanel.changepassword", true), "changepassword");
        $changepasswordfield->attach($fields->fieldSelect("meta[changepassword]", $select_options, $this->Html->ifSet($vars->meta['changepassword']), array('id' => "changepassword")));
        $fields->setField($changepasswordfield);

        $emailfield = $fields->label(Language::_("tastycpanel.email", true), "email");
        $emailfield->attach($fields->fieldSelect("meta[email]", $select_options, $this->Html->ifSet($vars->meta['email']), array('id' => "email")));
        $fields->setField($emailfield);

        $emailforwardersfield = $fields->label(Language::_("tastycpanel.emailforwarders", true), "emailforwarders");
        $emailforwardersfield->attach($fields->fieldSelect("meta[emailforwarders]", $select_options, $this->Html->ifSet($vars->meta['emailforwarders']), array('id' => "emailforwarders")));
        $fields->setField($emailforwardersfield);

        $ftpaccountsfield = $fields->label(Language::_("tastycpanel.ftpaccounts", true), "ftpaccounts");
        $ftpaccountsfield->attach($fields->fieldSelect("meta[ftpaccounts]", $select_options, $this->Html->ifSet($vars->meta['ftpaccounts']), array('id' => "ftpaccounts")));
        $fields->setField($ftpaccountsfield);

        $subdomainsfield = $fields->label(Language::_("tastycpanel.subdomains", true), "subdomains");
        $subdomainsfield->attach($fields->fieldSelect("meta[subdomains]", $select_options, $this->Html->ifSet($vars->meta['subdomains']), array('id' => "subdomains")));
        $fields->setField($subdomainsfield);

        $addondomainsfield = $fields->label(Language::_("tastycpanel.addondomains", true), "addondomains");
        $addondomainsfield->attach($fields->fieldSelect("meta[addondomains]", $select_options, $this->Html->ifSet($vars->meta['addondomains']), array('id' => "addondomains")));
        $fields->setField($addondomainsfield);

        $parkeddomainsfield = $fields->label(Language::_("tastycpanel.parkeddomains", true), "parkeddomains");
        $parkeddomainsfield->attach($fields->fieldSelect("meta[parkeddomains]", $select_options, $this->Html->ifSet($vars->meta['parkeddomains']), array('id' => "parkeddomains")));
        $fields->setField($parkeddomainsfield);

        $databasesfield = $fields->label(Language::_("tastycpanel.databases", true), "databases");
        $databasesfield->attach($fields->fieldSelect("meta[databases]", $select_options, $this->Html->ifSet($vars->meta['databases']), array('id' => "databases")));
        $fields->setField($databasesfield);

        $cronjobsfield = $fields->label(Language::_("tastycpanel.cronjobs", true), "cronjobs");
        $cronjobsfield->attach($fields->fieldSelect("meta[cronjobs]", $select_options, $this->Html->ifSet($vars->meta['cronjobs']), array('id' => "cronjobs")));
        $fields->setField($cronjobsfield);

        $backupsfield = $fields->label(Language::_("tastycpanel.backups", true), "backups");
        $backupsfield->attach($fields->fieldSelect("meta[backups]", $select_options, $this->Html->ifSet($vars->meta['backups']), array('id' => "backups")));
        $fields->setField($backupsfield);

        $ipblockerfield = $fields->label(Language::_("tastycpanel.ipblocker", true), "ipblocker");
        $ipblockerfield->attach($fields->fieldSelect("meta[ipblocker]", $select_options, $this->Html->ifSet($vars->meta['ipblocker']), array('id' => "ipblocker")));
        $fields->setField($ipblockerfield);


        $firewall = $fields->label(Language::_("tastycpanel.firewall_admin", true), "firewall");
        $firewall->attach($fields->fieldSelect("meta[firewall]", $select_options, $this->Html->ifSet($vars->meta['firewall']), array('id' => "firewall")));
        $fields->setField($firewall);

        if ($vars->meta['firewall'] === "true") {
            $firewall_options = array('show' => "Show & Allow Client To Enter The IP Address To Unblock it From CSF", 'hide' => "Use the Client's IP Address");
            $firewall_ad = $fields->label(Language::_("tastycpanel.firewall_ip", true), "firewall_ip");
            $firewall_ad->attach($fields->fieldSelect("meta[firewall_ip]", $firewall_options, $this->Html->ifSet($vars->meta['firewall_ip']), array('id' => "firewall_ip")));
            $fields->setField($firewall_ad);
        }
        $manage_select = array('false' => "Disable", 'softaculous' => "Softaculous", 'installatron' => "Installatron");
        $manageapps = $fields->label(Language::_("tastycpanel.manageapps", true), "manageapps");
        $manageapps->attach($fields->fieldSelect("meta[manageapps]", $manage_select, $this->Html->ifSet($vars->meta['manageapps']), array('id' => "manageapps")));
        $fields->setField($manageapps);

        if (isset($vars->meta['type']) && $vars->meta['type'] === "reseller") {
            $whmfield = $fields->label(Language::_("tastycpanel.whm_login", true), "whm_login");
            $whmfield->attach($fields->fieldSelect("meta[whm_login]", $select_options, $this->Html->ifSet($vars->meta['whm_login']), array('id' => "whm_login")));
            $fields->setField($whmfield);
        }

        $cpanelfield = $fields->label(Language::_("tastycpanel.cpanel_login", true), "cpanel_login");
        $cpanelfield->attach($fields->fieldSelect("meta[cpanel_login]", $select_options, $this->Html->ifSet($vars->meta['cpanel_login']), array('id' => "cpanel_login")));
        $fields->setField($cpanelfield);

        $webmailfield = $fields->label(Language::_("tastycpanel.webmail_login", true), "webmail_login");
        $webmailfield->attach($fields->fieldSelect("meta[webmail_login]", $select_options, $this->Html->ifSet($vars->meta['webmail_login']), array('id' => "webmail_login")));
        $fields->setField($webmailfield);

        $phpmyadminfield = $fields->label(Language::_("tastycpanel.phpmyadmin_login", true), "phpmyadmin_login");
        $phpmyadminfield->attach($fields->fieldSelect("meta[phpmyadmin_login]", $select_options, $this->Html->ifSet($vars->meta['phpmyadmin_login']), array('id' => "phpmyadmin_login")));
        $fields->setField($phpmyadminfield);

        $filemanager_login = $fields->label(Language::_("tastycpanel.filemanager_login", true), "filemanager_login");
        $filemanager_login->attach($fields->fieldSelect("meta[filemanager_login]", $select_options, $this->Html->ifSet($vars->meta['filemanager_login']), array('id' => "filemanager_login")));
        $fields->setField($filemanager_login);

        return $fields;
    }

    public function getEmailTags() {
        return array(
            'module' => array('hostname', 'name_servers'),
            'package' => array('type', 'package'),
            'service' => array('username', 'password', 'domain_name', 'custom_bwlimit', 'custom_quota', 'dedicated_ip')
        );
    }

    public function addPackage(array $vars = null) {


        $this->Input->setRules($this->getPackageRules($vars));


        $meta = array();
        if ($this->Input->validates($vars)) {



            foreach ($vars['meta'] as $key => $value) {
                $meta[] = array(
                    'key' => $key,
                    'value' => $value,
                    'encrypted' => 0
                );
            }
        }
        return $meta;
    }

    public function editPackage($package, array $vars = null) {


        $this->Input->setRules($this->getPackageRules($vars));


        $meta = array();
        if ($this->Input->validates($vars)) {


            foreach ($vars['meta'] as $key => $value) {
                $meta[] = array(
                    'key' => $key,
                    'value' => $value,
                    'encrypted' => 0
                );
            }
        }
        return $meta;
    }

    public function manageModule($module, array &$vars) {

        $this->view = new View("manage", "default");
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView("components" . DS . "modules" . DS . "tastycpanelmodule" . DS);


        Loader::loadHelpers($this, array("Form", "Html", "Widget"));
        foreach ($module->rows as $key => $value) {
            $whm = $this->getcPanelApi($module->rows[$key]->meta->accesskey, $module->rows[$key]->meta->hostname, $module->rows[$key]->meta->username, $module->rows[$key]->meta->use_ssl, false);
            $module->rows[$key]->meta->getdiskusage = $whm->getdiskusage();
            $module->rows[$key]->meta->serverloadavg = $whm->serverloadavg()->one;
        }

        $this->view->set("module", $module);

        return $this->view->fetch();
    }

    public function manageAddRow(array &$vars) {

        $this->view = new View("add_row", "default");
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView("components" . DS . "modules" . DS . "tastycpanelmodule" . DS);


        Loader::loadHelpers($this, array("Form", "Html", "Widget"));

        $this->view->set("vars", (object) $vars);
        return $this->view->fetch();
    }

    public function manageEditRow($module_row, array &$vars) {
        $this->view = new View("edit_row", "default");
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView("components" . DS . "modules" . DS . "tastycpanelmodule" . DS);


        Loader::loadHelpers($this, array("Form", "Html", "Widget"));

        if (empty($vars))
            $vars = $module_row->meta;

        $this->view->set("vars", (object) $vars);
        return $this->view->fetch();
    }

    public function addModuleRow(array &$vars) {
        $meta_fields = array("hostname", "accesskey", "username", "accountlimit", "account_count", "use_ssl", "name_servers");
        $encrypted_fields = array("hostname", "accesskey", "username", "accountlimit", "account_count", "use_ssl", "name_servers");

        $this->Input->setRules($this->getRowRules($vars));


        if ($this->Input->validates($vars)) {


            $meta = array();
            foreach ($vars as $key => $value) {

                if (in_array($key, $meta_fields)) {
                    $meta[] = array(
                        'key' => $key,
                        'value' => $value,
                        'encrypted' => in_array($key, $encrypted_fields) ? 1 : 0
                    );
                }
            }

            return $meta;
        }
    }

    public function editModuleRow($module_row, array &$vars) {
        $meta_fields = array("hostname", "accesskey", "username", "accountlimit", "account_count", "use_ssl", "name_servers");
        $encrypted_fields = array("hostname", "accesskey", "username", "accountlimit", "account_count", "use_ssl", "name_servers");

        $this->Input->setRules($this->getRowRules($vars));


        if ($this->Input->validates($vars)) {


            $meta = array();
            foreach ($vars as $key => $value) {

                if (in_array($key, $meta_fields)) {
                    $meta[] = array(
                        'key' => $key,
                        'value' => $value,
                        'encrypted' => in_array($key, $encrypted_fields) ? 1 : 0
                    );
                }
            }

            return $meta;
        }
    }

    public function deleteModuleRow($module_row) {

    }

    public function getServiceName($service) {
        foreach ($service->fields as $field) {
            if ($field->key == "domain_name")
                return $field->value;
        }
        return null;
    }

    public function getPackageServiceName($package, array $vars = null) {
        if (isset($vars['domain_name']))
            return $vars['domain_name'];
        return null;
    }

    public function getAdminAddFields($package, $vars = null) {
        Loader::loadHelpers($this, array("Html"));

        $fields = new ModuleFields();

        $domain = $fields->label(Language::_("tastycpanel.service.domain", true), "domain_name");
        $domain->attach($fields->fieldText("domain_name", $this->Html->ifSet($vars->domain_name, $this->Html->ifSet($vars->domain_name)), array('id' => "domain_name")));
        $fields->setField($domain);

        return $fields;
    }

    public function getClientAddFields($package, $vars = null) {
        Loader::loadHelpers($this, array("Html"));

        $fields = new ModuleFields();
        $domain = $fields->label(Language::_("tastycpanel.service.domain", true), "domain_name");
        $domain->attach($fields->fieldText("domain_name", $this->Html->ifSet($vars->domain_name, $this->Html->ifSet($vars->domain_name)), array('id' => "domain_name")));
        $fields->setField($domain);

        return $fields;
    }

    public function getAdminEditFields($package, $vars = null) {

        $fields = new ModuleFields();

        $domain = $fields->label(Language::_("tastycpanel.service.domain", true), "domain_name");
        $domain->attach($fields->fieldText("domain_name", $this->Html->ifSet($vars->domain_name, $this->Html->ifSet($vars->domain_name)), array('id' => "domain_name")));
        $fields->setField($domain);

        $username = $fields->label(Language::_("tastycpanel.service.username", true), "username");
        $username->attach($fields->fieldText("username", $this->Html->ifSet($vars->username, $this->Html->ifSet($vars->username)), array('id' => "username")));
        $fields->setField($username);

        $password = $fields->label(Language::_("tastycpanel.service.password", true), "password");
        $password->attach($fields->fieldPassword("password", array('id' => "password", 'value' => "{$this->Html->ifSet($vars->password, $this->Html->ifSet($vars->password))}")));
        $fields->setField($password);

        $dedicated_ip = $fields->label(Language::_("tastycpanel.dedicated_ip", true), "password");
        $dedicated_ip->attach($fields->fieldText("dedicated_ip", $this->Html->ifSet($vars->dedicated_ip, $this->Html->ifSet($vars->dedicated_ip)), array('id' => "dedicated_ip")));
        $fields->setField($dedicated_ip);

        return $fields;
    }

    /**
     * Attempts to validate service info. This is the top-level error checking method. Sets Input errors on failure.
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param array $vars An array of user supplied info to satisfy the request
     * @return bool True if the service validates, false otherwise. Sets Input errors when false.
     */
    public function validateService($package, array $vars = null)
    {
        $this->Input->setRules($this->getServiceRules($vars));
        return $this->Input->validates($vars);
    }

    /**
     * Attempts to validate an existing service against a set of service info updates. Sets Input errors on failure.
     *
     * @param stdClass $service A stdClass object representing the service to validate for editing
     * @param array $vars An array of user-supplied info to satisfy the request
     * @return bool True if the service update validates or false otherwise. Sets Input errors when false.
     */
    public function validateServiceEdit($service, array $vars = null)
    {
        $this->Input->setRules($this->getServiceRules($vars, true));
        return $this->Input->validates($vars);
    }

    /**
     * Returns the rule set for adding/editing a service
     *
     * @param array $vars A list of input vars
     * @param bool $edit True to get the edit rules, false for the add rules
     * @return array Service rules
     */
    private function getServiceRules(array $vars = null, $edit = false)
    {

        $rules = array(
            'domain_name' => array(
                'format' => array(
                    'rule' => array(array($this, "hostnameValidator")),
                    'message' => Language::_("tastycpanel.error.service.domain.format", true)
                ),
                'test' => array(
                    'rule' => array("substr_compare", "test", 0, 4, true),
                    'message' => Language::_("tastycpanel.error.service.domain.test", true)
                )
            )
        );

        if (!isset($vars['domain_name']) || strlen($vars['domain_name']) < 4) {
            unset($rules['domain_name']['test']);
        }

        if ($edit) {
            $rules['username'] = array(
                'format' => array(
                    'if_set' => true,
                    'rule' => array("matches", "/^[a-z]([a-z0-9])*$/i"),
                    'message' => Language::_("tastycpanel.error.service.username.format", true)
                ),
                'test' => array(
                    'if_set' => true,
                    'rule' => array("matches", "/^(?!test)/"),
                    'message' => Language::_("tastycpanel.error.service.username.test", true)
                ),
                'length' => array(
                    'if_set' => true,
                    'rule' => array("betweenLength", 1, 8),
                    'message' => Language::_("tastycpanel.error.service.username.length", true)
                )
            );
            $rules['password'] = array(
                'valid' => array(
                    'if_set' => true,
                    'rule' => array("isPassword", 8),
                    'message' => Language::_("tastycpanel.error.service.password.valid", true),
                    'last' => true
                )
            );
        }

        return $rules;
    }

    private function generatePassword($min_length = 10, $max_length = 14) {
        $pool = "abcdefghijklmnopqrstuvwxyz0123456789";
        $pool_size = strlen($pool);
        $length = mt_rand(max($min_length, 5), min($max_length, 14));
        $password = "";

        for ($i = 0; $i < $length; $i++) {
            $password .= substr($pool, mt_rand(0, $pool_size - 1), 1);
        }

        return $password;
    }

    private function getUserAccount($name, $package = null) {
        $user = null;
        $module_row = $this->getModuleRow();

        if ($module_row)
            $whm = $this->getcPanelApi($module_row->meta->accesskey, $module_row->meta->hostname, $module_row->meta->username, $module_row->meta->use_ssl, false);


        try {
            if ($api) {
                $output = $whm->listaccts("user", $name);

                if (isset($output))
                    $user = $output;
            }
        } catch (Exception $e) {

        }

        return $user;
    }

    private function generateUsername($host_name) {
        $username = ltrim(preg_replace('/[^a-z0-9]/i', '', $host_name), '0123456789');

        $length = strlen($username);
        $pool = "abcdefghijklmnopqrstuvwxyz0123456789";
        $pool_size = strlen($pool);

        if ($length < 5) {
            for ($i = $length; $i < 8; $i++) {
                $username .= substr($pool, mt_rand(0, $pool_size - 1), 1);
            }
            $length = strlen($username);
        }

        $username = substr($username, 0, min($length, 8));

        $account_matching_characters = 4; // [1,4]
        $accounts = $this->getUserAccount(substr($username, 0, $account_matching_characters) . "(.*)");

        if (!empty($accounts)) {
            foreach ($accounts as $key => $account) {
                $accounts[$account->user] = $account;
                unset($accounts[$key]);
            }

            if (array_key_exists($username, $accounts)) {
                for ($i = 0; $i < (int) str_repeat(9, $account_matching_characters); $i++) {
                    $new_username = substr($username, 0, -$account_matching_characters) . $i;
                    if (!array_key_exists($new_username, $accounts)) {
                        $username = $new_username;
                        break;
                    }
                }
            }
        }

        return $username;
    }

    private function getFieldsFromInput(array $vars, $package) {
        $fields = array(
            'domain' => isset($vars['domain_name']) ? $vars['domain_name'] : null,
            'plan' => $package->meta->package,
            'username' => $this->generateUsername($vars['domain_name']),
            'password' => $this->generatePassword(),
            'reseller' => ($package->meta->type == "reseller" ? 1 : 0)
        );
        $fields['acls'] = isset($package->meta->acls) ? $package->meta->acls : null;
        Loader::loadModels($this, array("Clients"));
        if (isset($vars['client_id']) && ($client = $this->Clients->get($vars['client_id'], false))) {
            $fields['contactemail'] = $client->email;
        }
        if (isset($vars['configoptions']['dedicated_ip']) && $vars['configoptions']['dedicated_ip'] == "y") {
            $fields['ip'] = "y";
        }
        if ($package->meta->additionaldisk == true) {
            if (isset($vars['custom_quota']) && $vars['custom_quota'] !== "disable") {
                $fields['quota'] = isset($vars['custom_bwlimit']) ? $vars['custom_bwlimit'] : null;
            }
        }
        if ($package->meta->additionalbandwidth == true) {
            if (isset($vars['custom_bwlimit']) && $vars['custom_bwlimit'] !== "disable") {
                $fields['bwlimit'] = isset($vars['custom_bwlimit']) ? $vars['custom_bwlimit'] : null;
            }
        }
        return $fields;
    }

    public function addService($package, array $vars = null, $parent_package = null, $parent_service = null, $status = "pending") {
        // Get the module row used for this service
        $module_row = $this->getModuleRow($package->module_row);



        // Attempt to validate the input and return on failure
        $this->validateService($package, $vars);
        if ($this->Input->errors())
            return;


        $params = $this->getFieldsFromInput((array) $vars, $package);

        // Only provision the service remotely if 'use_module' is true
        if (isset($vars['use_module']) && $vars['use_module'] == "true") {
            $whm = $this->getcPanelApi($module_row->meta->accesskey, $module_row->meta->hostname, $module_row->meta->username, $module_row->meta->use_ssl, false);
            $createaccount = $whm->createacct($params);
            $whm->apiQuery("forcepasswordchange", array('users_json' => $params['username'], 'stop_on_failure' => 1));

            if (isset($createaccount->metadata->result) && $createaccount->metadata->result === 0) {
                $fa = array(
                    0 => array(
                        "result" => $createaccount->metadata->reason
                    )
                );
                $this->Input->setErrors($fa);
            } else {
                if ($package->meta->type == "reseller" && (isset($package->meta->acls) && $package->meta->acls != "")) {
                    $whm->setacls(array('reseller' => $params['username'], 'acllist' => $package->meta->acls));
                }
                $this->updateAccountCounter($module_row);
            }
            $this->log($module_row->meta->hostname . "|createacct", null, "input", true);


            // Return on error
            if ($this->Input->errors())
                return;
        }

        // Return the service fields
        return array(
            array(
                'key' => "domain_name",
                'value' => (isset($params['domain']) ? $params['domain'] : null),
                'encrypted' => 0
            ),
            array(
                'key' => "username",
                'value' => (isset($params['username']) ? $params['username'] : null),
                'encrypted' => 1
            ),
            array(
                'key' => "password",
                'value' => (isset($params['password']) ? $params['password'] : null),
                'encrypted' => 1
            ),
            array(
                'key' => "custom_quota",
                'value' => (isset($vars['configoptions']['quota']) ? $vars['configoptions']['quota'] : null),
                'encrypted' => 0
            ),
            array(
                'key' => "custom_bwlimit",
                'value' => (isset($vars['configoptions']['bwlimit']) ? $vars['configoptions']['bwlimit'] : null),
                'encrypted' => 0
            ),
            array(
                'key' => "dedicated_ip",
                'value' => (isset($createaccount->result[0]->ip) && ($params['ip'] == "y") ? $createaccount->result[0]->ip : null),
                'encrypted' => 0
            )
        );
    }

    public function editService($package, $service, array $vars = null, $parent_package = null, $parent_service = null) {
        $module_row = $this->getModuleRow($package->module_row);

        if ($this->Input->errors())
            return;

        $service_fields = $this->serviceFieldsToObject($service->fields);
        $whm = $this->getcPanelApi($module_row->meta->accesskey, $module_row->meta->hostname, $module_row->meta->username, $module_row->meta->use_ssl, false);


        $this->validateServiceEdit($service, $vars);

        if ($this->Input->errors())
            return;

        if ($vars['use_module'] == "true") {


            if ($service_fields->domain_name !== $vars['domain_name']) {
                $modify_account = $whm->modifyacct($service_fields->username, array("DNS" => $vars['domain_name']));
                if (isset($modify_account->result) && $modify_account->result == 0 && isset($modify_account->reason)) {
                    $fa = array(
                        0 => array(
                            "result" => $modify_account->reason
                        )
                    );
                    $this->Input->setErrors($fa[0]);
                    return;
                }
                $this->log($module_row->meta->hostname . "|modifyacct", serialize("domain_change"), "input", true);
            }
            if ($service_fields->password !== $vars['password']) {
                $change_password = $whm->passwd($service_fields->username, $vars['password']);
                if (isset($change_password->result) && $change_password->result == 0 && isset($change_password->reason)) {
                    $fa = array(
                        0 => array(
                            "result" => $change_password->reason
                        )
                    );
                    $this->Input->setErrors($fa[0]);
                    return;
                }
                $this->log($module_row->meta->hostname . "- {$service_fields->username}|passwd", serialize("password_change"), "input", true);
            }
            if ($service_fields->dedicated_ip !== $vars['dedicated_ip'] && $vars['configoptions']['dedicated_ip'] == "y") {
                $modify_account = $whm->setsiteip(array("ip" => $vars['dedicated_ip'], "domain" => $vars['user'], "domain" => $vars['domain']));
                $this->log($module_row->meta->hostname . "|setsiteip", serialize("change_ip"), "input", true);
            }


            if ($service_fields->custom_quota !== $vars['configoptions']['quota']) {
                $modify_account = $whm->modifyacct($service_fields->username, array("QUOTA" => $vars['configoptions']['quota']));
                if (isset($modify_account->result) && $modify_account->result == 0 && isset($modify_account->reason)) {
                    $fa = array(
                        0 => array(
                            "result" => $modify_account->reason
                        )
                    );
                    $this->Input->setErrors($fa[0]);
                    return;
                }
                $this->log($module_row->meta->hostname . "|modifyacct", serialize("quota_change"), "input", true);
            }

            if ($service_fields->custom_bwlimit !== $vars['configoptions']['bwlimit']) {
                $modify_account = $whm->modifyacct($service_fields->username, array("BWLIMIT" => $vars['configoptions']['bwlimit']));
                if (isset($modify_account->result) && $modify_account->result == 0 && isset($modify_account->reason)) {
                    $fa = array(
                        0 => array(
                            "result" => $modify_account->reason
                        )
                    );
                    $this->Input->setErrors($fa[0]);
                    return;
                }
                $this->log($module_row->meta->hostname . "|modifyacct", serialize("bandwidth_limit_change"), "input", true);
            }
            if ($service_fields->username !== $vars['username']) {
                $modify_account = $whm->modifyacct($service_fields->username, array("newuser" => $vars['username']));
                if (isset($modify_account->result) && $modify_account->result == 0 && isset($modify_account->reason)) {
                    $fa = array(
                        0 => array(
                            "result" => $modify_account->reason
                        )
                    );
                    $this->Input->setErrors($fa[0]);
                    return;
                }
                $this->log($module_row->meta->hostname . "|modifyacct", serialize("username_change"), "input", true);
            }



            if ($this->Input->errors()) {
                return;
            }
        }

        return array(
            array(
                'key' => "domain_name",
                'value' => $vars['domain_name'],
                'encrypted' => 0
            ),
            array(
                'key' => "username",
                'value' => $vars['username'],
                'encrypted' => 1
            ),
            array(
                'key' => "password",
                'value' => $vars['password'],
                'encrypted' => 1
            ),
            array(
                'key' => "custom_quota",
                'value' => $vars['configoptions']['quota'],
                'encrypted' => 0
            ),
            array(
                'key' => "custom_bwlimit",
                'value' => $vars['configoptions']['bwlimit'],
                'encrypted' => 0
            ),
            array(
                'key' => "dedicated_ip",
                'value' => $vars['dedicated_ip'],
                'encrypted' => 0
            )
        );
    }

    public function suspendService($package, $service, $parent_package = null, $parent_service = null) {
        $module_row = $this->getModuleRow($package->module_row);

        if ($module_row) {
            $whm = $this->getcPanelApi($module_row->meta->accesskey, $module_row->meta->hostname, $module_row->meta->username, $module_row->meta->use_ssl, false);

            $service_fields = $this->serviceFieldsToObject($service->fields);

            if ($package->meta->type == "reseller") {
                $this->log($module_row->meta->hostname . "|suspendreseller", serialize($service_fields->username), "input", true);
                $whm->suspendreseller(array("user" => $service_fields->username));
            } else {
                $this->log($module_row->meta->hostname . "|suspendacct", serialize($service_fields->username), "input", true);
                $whm->suspendacct(array("user" => $service_fields->username));
            }
        }

        return null;
    }

    public function unsuspendService($package, $service, $parent_package = null, $parent_service = null) {
        $module_row = $this->getModuleRow($package->module_row);

        if ($module_row) {
            $whm = $this->getcPanelApi($module_row->meta->accesskey, $module_row->meta->hostname, $module_row->meta->username, $module_row->meta->use_ssl, false);

            $service_fields = $this->serviceFieldsToObject($service->fields);

            if ($package->meta->type == "reseller") {
                $this->log($module_row->meta->hostname . "|unsuspendreseller", serialize($service_fields->username), "input", true);
                $whm->unsuspendreseller(array("user" => $service_fields->username));
            } else {
                $this->log($module_row->meta->hostname . "|unsuspendacct", serialize($service_fields->username), "input", true);
                $whm->unsuspendacct(array("user" => $service_fields->username));
            }
        }

        return null;
    }

    public function cancelService($package, $service, $parent_package = null, $parent_service = null) {
        $module_row = $this->getModuleRow($package->module_row);

        if ($module_row) {
            $whm = $this->getcPanelApi($module_row->meta->accesskey, $module_row->meta->hostname, $module_row->meta->username, $module_row->meta->use_ssl, false);

            $service_fields = $this->serviceFieldsToObject($service->fields);

            if ($package->meta->type == "reseller") {
                $this->log($module_row->meta->hostname . "|terminatereseller", serialize($service_fields->username), "input", true);
                $whm->terminatereseller(array("user" => $service_fields->username));
            } else {
                $this->log($module_row->meta->hostname . "|removeacct", serialize($service_fields->username), "input", true);
                $whm->removeacct(array("user" => $service_fields->username));
            }
        }

        return null;
    }

    public function changeServicePackage($package_from, $package_to, $service, $parent_package = null, $parent_service = null) {
        if (($module_row = $this->getModuleRow($package_from->module_row))) {
            $whm = $this->getcPanelApi($module_row->meta->accesskey, $module_row->meta->hostname, $module_row->meta->username, $module_row->meta->use_ssl, false);

            if ($package_from->meta->package != $package_to->meta->package) {

                $service_fields = $this->serviceFieldsToObject($service->fields);

                $this->log($module_row->meta->hostname . "|changepackage", serialize($service_fields->username), "input", true);

                $modify_account = $whm->changepackage(array("user" => $service_fields->username, 'pkg' => $package_to->meta->package));
                if (isset($modify_account->result) && $modify_account->result == 0 && isset($modify_account->reason)) {
                    $fa = array(
                        0 => array(
                            "result" => $modify_account->reason
                        )
                    );
                    $this->Input->setErrors($fa[0]);
                    return;
                }
            }
        }
        return null;
    }

    public function getAdminServiceInfo($service, $package) {

        $row = $this->getModuleRow($package->module_row);

        $this->view = new View("admin_service_info", "default");
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView("components" . DS . "modules" . DS . "tastycpanelmodule" . DS);

        Loader::loadHelpers($this, array("Form", "Html"));
        $this->view->set("module_row", $row);
        $this->view->set("package", $package);
        $this->view->set("service", $service);
        $this->view->set("service_fields", $this->serviceFieldsToObject($service->fields));

        return $this->view->fetch();
    }

    public function getClientServiceInfo($service, $package) {
        $row = $this->getModuleRow($package->module_row);
        $this->view = new View("client_service_info", "default");
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView("components" . DS . "modules" . DS . "tastycpanelmodule" . DS);

        Loader::loadHelpers($this, array("Form", "Html"));

        $this->view->set("module_row", $row);
        $this->view->set("package", $package);
        $this->view->set("service", $service);
        $this->view->set("service_fields", $this->serviceFieldsToObject($service->fields));

        return $this->view->fetch();
    }

    public function validateConnection($key, $hostname, $username, $use_ssl) {
        $whm = $this->getcPanelApi($key, $hostname, $username, $use_ssl, false);
        $validate = $whm->listpkgs();
        if (is_array($validate)) {
            return true;
        }
        return false;
    }

    private function getcPanelApi($key, $hostname, $username, $use_ssl, $cpanel = true) {
        Loader::load(dirname(__FILE__) . DS . "apis" . DS . "cpanel_api.php");
        $http = null;
        $port = null;

        if ($cpanel == TRUE) {
            if ($use_ssl == TRUE) {
                $http = "https";
                $port = "2083";
            } else {
                $http = "http";
                $port = "2082";
            }
        } else {
            if ($use_ssl == TRUE) {
                $http = "https";
                $port = "2087";
            } else {
                $http = "http";
                $port = "2086";
            }
        }
        $api = new cPanelAPI($username, $key, $http, $hostname, $port);

        return $api;
    }

    private function accountCounter($api) {
        $accounts = false;

        try {
            $output = $api->listaccts();

            if (isset($output->acct))
                $accounts = count($output->acct);
        } catch (Exception $e) {

        }
        return $accounts;
    }

    private function updateAccountCounter($module_row) {
        $api = $this->getcPanelApi($module_row->meta->accesskey, $module_row->meta->hostname, $module_row->meta->username, $module_row->meta->use_ssl, false);
        if (($count = $this->accountCounter($api)) !== false) {
            Loader::loadModels($this, array("ModuleManager"));
            $vars = $this->ModuleManager->getRowMeta($module_row->id);

            if ($vars) {
                $vars->account_count = $count;
                $vars = (array) $vars;

                $this->ModuleManager->editRow($module_row->id, $vars);
            }
        }
    }

    private function getRowRules(&$vars) {
        $rules = array(
            'hostname' => array(
                'empty' => array(
                    'rule' => "isEmpty",
                    'negate' => true,
                    'message' => Language::_("tastycpanel.error.row.hostname", true)
                ),
                'valid' => array(
                    'rule' => array(array($this, "hostnameValidator")),
                    'message' => Language::_("tastycpanel.error.row.hostname", true)
                )
            ),
            'username' => array(
                'valid' => array(
                    'rule' => "isEmpty",
                    'negate' => true,
                    'message' => Language::_("tastycpanel.error.row.username", true)
                )
            ),
            'accountlimit' => array(
                'empty' => array(
                    'rule' => "isEmpty",
                    'negate' => true,
                    'message' => Language::_("tastycpanel.error.row.accountlimit", true)
                ),
                'valid' => array(
                    'rule' => array("matches", "/^([0-9]+)?$/"),
                    'message' => Language::_("tastycpanel.error.row.accountlimit_numeric", true)
                )
            ),
            'name_servers' => array(
                'count' => array(
                    'rule' => array(array($this, "nsCountValidator")),
                    'message' => Language::_("tastycpanel.!error.name_servers_count", true)
                ),
                'valid' => array(
                    'rule' => array(array($this, "nsValidator")),
                    'message' => Language::_("tastycpanel.error.row.nameservers", true)
                )
            ),
            'accesskey' => array(
                'valid' => array(
                    'last' => true,
                    'rule' => "isEmpty",
                    'negate' => true,
                    'message' => Language::_("tastycpanel.error.row.accesskey", true)
                ),
                'valid_connection' => array(
                    'rule' => array(array($this, "validateConnection"), $vars['hostname'], $vars['username'], $vars['use_ssl']),
                    'message' => Language::_("tastycpanel.error.row.accesskeyconnection", true)
                )
            )
        );


        return $rules;
    }

    public function hostnameValidator($host_name) {
        if (strlen($host_name) > 255)
            return false;

        return $this->Input->matches($host_name, "/^([a-z0-9]|[a-z0-9][a-z0-9\-]{0,61}[a-z0-9])(\.([a-z0-9]|[a-z0-9][a-z0-9\-]{0,61}[a-z0-9]))+$/");
    }

    public function nsCountValidator($name_servers) {
        if (is_array($name_servers) && count($name_servers) >= 2)
            return true;

        return false;
    }

    public function nsValidator($name_servers) {
        if (is_array($name_servers)) {
            foreach ($name_servers as $name_server) {
                if (!$this->hostnameValidator($name_server))
                    return false;
            }
        }
        return true;
    }

    private function getPackageRules($vars) {
        $rules = array(
            'meta[package]' => array(
                'valid' => array(
                    'rule' => "isEmpty",
                    'negate' => true,
                    'message' => Language::_("tastycpanel.error.package.package", true)
                )
            ),
            'meta[type]' => array(
                'valid' => array(
                    'rule' => "isEmpty",
                    'negate' => true,
                    'message' => Language::_("tastycpanel.error.package.type", true)
                )
            )
        );

        return $rules;
    }

    public function cronSelectValues($select_name) {
        if ($select_name === "min") {
            return array(
                "--" => "-- Common Settings --",
                "*" => "Every minute(*)",
                "*/2" => "Every other minute(*/2)",
                "*/5" => "Every 5 minutes(*/5)",
                "*/10" => "Every 10 minutes(*/10)",
                "*/15" => "Every 15 minutes(*/15)",
                "0,30" => "Every 30 minutes(0,30)",
                "--" => "-- Minutes --",
                "0" => ":00 top of the hour (0)",
                "1" => ":01 (1)",
                "2" => ":02 (2)",
                "3" => ":03 (3)",
                "4" => ":04 (4)",
                "5" => ":05 (5)",
                "6" => ":06 (6)",
                "7" => ":07 (7)",
                "8" => ":08 (8)",
                "9" => ":09 (9)",
                "10" => ":10 (10)",
                "11" => ":11 (11)",
                "12" => ":12 (12)",
                "13" => ":13 (13)",
                "14" => ":14 (14)",
                "15" => ":15 quarter past (15)",
                "16" => ":16 (16)",
                "17" => ":17 (17)",
                "18" => ":18 (18)",
                "19" => ":19 (19)",
                "20" => ":20 (20)",
                "21" => ":21 (21)",
                "22" => ":22 (22)",
                "23" => ":23 (23)",
                "24" => ":24 (24)",
                "25" => ":25 (25)",
                "26" => ":26 (26)",
                "27" => ":27 (27)",
                "28" => ":28 (28)",
                "29" => ":29 (29)",
                "30" => ":30 half past (30)",
                "31" => ":31 (31)",
                "32" => ":32 (32)",
                "33" => ":33 (33)",
                "34" => ":34 (34)",
                "35" => ":35 (35)",
                "36" => ":36 (36)",
                "37" => ":37 (37)",
                "38" => ":38 (38)",
                "39" => ":39 (39)",
                "40" => ":40 (40)",
                "41" => ":41 (41)",
                "42" => ":42 (42)",
                "43" => ":43 (43)",
                "44" => ":44 (44)",
                "45" => ":45 quarter til (45)",
                "46" => ":46 (46)",
                "47" => ":47 (47)",
                "48" => ":48 (48)",
                "49" => ":49 (49)",
                "50" => ":50 (50)",
                "51" => ":51 (51)",
                "52" => ":52 (52)",
                "53" => ":53 (53)",
                "54" => ":54 (54)",
                "55" => ":55 (55)",
                "56" => ":56 (56)",
                "57" => ":57 (57)",
                "58" => ":58 (58)",
                "59" => ":59 (59)"
            );
        } else if ($select_name === "hour") {
            return array(
                "--" => "-- Common Settings --",
                "*" => "Every hour (*)",
                "*/2" => "Every other hour (*/2)",
                "*/3" => "Every 3 hours (*/3)",
                "*/4" => "Every 4 hours (*/4)",
                "*/6" => "Every 6 hours (*/6)",
                "0, 12" => "Every 12 hours (0, 12)",
                " --" => "-- Hours --",
                "0" => "12:00 a.m. midnight (0)",
                "1" => "1:00 a.m. (1)",
                "2" => "2:00 a.m. (2)",
                "3" => "3:00 a.m. (3)",
                "4" => "4:00 a.m. (4)",
                "5" => "5:00 a.m. (5)",
                "6" => "6:00 a.m. (6)",
                "7" => "7:00 a.m. (7)",
                "8" => "8:00 a.m. (8)",
                "9" => "9:00 a.m. (9)",
                "10" => "10:00 a.m. (10)",
                "11" => "11:00 a.m. (11)",
                "12" => "12:00 p.m. noon (12)",
                "13" => "1:00 p.m. (13)",
                "14" => "2:00 p.m. (14)",
                "15" => "3:00 p.m. (15)",
                "16" => "4:00 p.m. (16)",
                "17" => "5:00 p.m. (17)",
                "18" => "6:00 p.m. (18)",
                "19" => "7:00 p.m. (19)",
                "20" => "8:00 p.m. (20)",
                "21" => "9:00 p.m. (21)",
                "22" => "10:00 p.m. (22)",
                "23" => "11:00 p.m. (23)"
            );
        } else if ($select_name === "day") {
            return array(
                "--" => " -- Common Settings -- ",
                "*" => " Every day (*) ",
                "*/2" => " Every other day (*/2) ",
                "1,15" => " 1st and 15th (1,15) ",
                "--" => " -- Days -- ",
                "1" => " 1st (1) ",
                "2" => " 2nd (2) ",
                "3" => " 3rd (3) ",
                "4" => " 4th (4) ",
                "5" => " 5th (5) ",
                "6" => " 6th (6) ",
                "7" => " 7th (7) ",
                "8" => " 8th (8) ",
                "9" => " 9th (9) ",
                "10" => " 10th (10) ",
                "11" => " 11th (11) ",
                "12" => " 12th (12) ",
                "13" => " 13th (13) ",
                "14" => " 14th (14) ",
                "15" => " 15th (15) ",
                "16" => " 16th (16) ",
                "17" => " 17th (17) ",
                "18" => " 18th (18) ",
                "19" => " 19th (19) ",
                "20" => " 20th (20) ",
                "21" => " 21st (21) ",
                "22" => " 22nd (22) ",
                "23" => " 23rd (23) ",
                "24" => " 24th (24) ",
                "25" => " 25th (25) ",
                "26" => " 26th (26) ",
                "27" => " 27th (27) ",
                "28" => " 28th (28) ",
                "29" => " 29th (29) ",
                "30" => " 30th (30) ",
                "31" => " 31st (31) "
            );
        } else if ($select_name === "month") {
            return array(
                "--" => " -- Common Settings -- ",
                "*" => " Every month (*) ",
                "*/2" => " Every other month (*/2) ",
                "*/4" => " Every 3 months (*/4) ",
                "1,7" => " Every 6 months (1,7) ",
                "--" => " -- Months -- ",
                "1" => " January (1) ",
                "2" => " February (2) ",
                "3" => " March (3) ",
                "4" => " April (4) ",
                "5" => " May (5) ",
                "6" => " June (6) ",
                "7" => " July (7) ",
                "8" => " August (8) ",
                "9" => " September (9) ",
                "10" => " October (10) ",
                "11" => " November (11) ",
                "12" => " December (12) "
            );
        } else if ($select_name === "weekday") {
            return array(
                "--" => " -- Common Settings -- ",
                "*" => " Every weekday (*) ",
                "1-5" => " Mon thru Fri (1-5) ",
                "0,6" => " Sat and Sun (6,0) ",
                "1,3,5" => " Mon, Wed, Fri (1,3,5) ",
                "2,4" => " Tues, Thurs (2,4) ",
                "--" => " -- Weekdays -- ",
                "0" => " Sunday (0) ",
                "1" => " Monday (1) ",
                "2" => " Tuesday (2) ",
                "3" => " Wednesday (3) ",
                "4" => " Thursday (4) ",
                "5" => " Friday (5) ",
                "6" => " Saturday (6) "
            );
        }
    }

    private function returnsslcountry() {
        return '    <option value="">Choose a country.</option>
    <option value="AD">
        AD
        (Andorra)
    </option>
    <option value="AE">
        AE
        (United Arab Emirates)
    </option>
    <option value="AF">
        AF
        (Afghanistan)
    </option>
    <option value="AG">
        AG
        (Antigua and Barbuda)
    </option>
    <option value="AI">
        AI
        (Anguilla)
    </option>
    <option value="AL">
        AL
        (Albania)
    </option>
    <option value="AM">
        AM
        (Armenia)
    </option>
    <option value="AO">
        AO
        (Angola)
    </option>
    <option value="AQ">
        AQ
        (Antarctica)
    </option>
    <option value="AR">
        AR
        (Argentina)
    </option>
    <option value="AS">
        AS
        (American Samoa)
    </option>
    <option value="AT">
        AT
        (Austria)
    </option>
    <option value="AU">
        AU
        (Australia)
    </option>
    <option value="AW">
        AW
        (Aruba)
    </option>
    <option value="AX">
        AX
        (Åland Islands)
    </option>
    <option value="AZ">
        AZ
        (Azerbaijan)
    </option>
    <option value="BA">
        BA
        (Bosnia and Herzegovina)
    </option>
    <option value="BB">
        BB
        (Barbados)
    </option>
    <option value="BD">
        BD
        (Bangladesh)
    </option>
    <option value="BE">
        BE
        (Belgium)
    </option>
    <option value="BF">
        BF
        (Burkina Faso)
    </option>
    <option value="BG">
        BG
        (Bulgaria)
    </option>
    <option value="BH">
        BH
        (Bahrain)
    </option>
    <option value="BI">
        BI
        (Burundi)
    </option>
    <option value="BJ">
        BJ
        (Benin)
    </option>
    <option value="BL">
        BL
        (Saint Barthélemy)
    </option>
    <option value="BM">
        BM
        (Bermuda)
    </option>
    <option value="BN">
        BN
        (Brunei)
    </option>
    <option value="BO">
        BO
        (Bolivia)
    </option>
    <option value="BQ">
        BQ
        (British Antarctic Territory)
    </option>
    <option value="BR">
        BR
        (Brazil)
    </option>
    <option value="BS">
        BS
        (Bahamas)
    </option>
    <option value="BT">
        BT
        (Bhutan)
    </option>
    <option value="BV">
        BV
        (Bouvet Island)
    </option>
    <option value="BW">
        BW
        (Botswana)
    </option>
    <option value="BY">
        BY
        (Belarus)
    </option>
    <option value="BZ">
        BZ
        (Belize)
    </option>
    <option value="CA">
        CA
        (Canada)
    </option>
    <option value="CC">
        CC
        (Cocos [Keeling] Islands)
    </option>
    <option value="CD">
        CD
        (Congo [DRC])
    </option>
    <option value="CF">
        CF
        (Central African Republic)
    </option>
    <option value="CG">
        CG
        (Congo [Republic])
    </option>
    <option value="CH">
        CH
        (Switzerland)
    </option>
    <option value="CI">
        CI
        (Ivory Coast)
    </option>
    <option value="CK">
        CK
        (Cook Islands)
    </option>
    <option value="CL">
        CL
        (Chile)
    </option>
    <option value="CM">
        CM
        (Cameroon)
    </option>
    <option value="CN">
        CN
        (China)
    </option>
    <option value="CO">
        CO
        (Colombia)
    </option>
    <option value="CR">
        CR
        (Costa Rica)
    </option>
    <option value="CU">
        CU
        (Cuba)
    </option>
    <option value="CV">
        CV
        (Cape Verde)
    </option>
    <option value="CW">
        CW

    </option>
    <option value="CX">
        CX
        (Christmas Island)
    </option>
    <option value="CY">
        CY
        (Cyprus)
    </option>
    <option value="CZ">
        CZ
        (Czech Republic)
    </option>
    <option value="DE">
        DE
        (Germany)
    </option>
    <option value="DJ">
        DJ
        (Djibouti)
    </option>
    <option value="DK">
        DK
        (Denmark)
    </option>
    <option value="DM">
        DM
        (Dominica)
    </option>
    <option value="DO">
        DO
        (Dominican Republic)
    </option>
    <option value="DZ">
        DZ
        (Algeria)
    </option>
    <option value="EC">
        EC
        (Ecuador)
    </option>
    <option value="EE">
        EE
        (Estonia)
    </option>
    <option value="EG">
        EG
        (Egypt)
    </option>
    <option value="EH">
        EH
        (Western Sahara)
    </option>
    <option value="ER">
        ER
        (Eritrea)
    </option>
    <option value="ES">
        ES
        (Spain)
    </option>
    <option value="ET">
        ET
        (Ethiopia)
    </option>
    <option value="FI">
        FI
        (Finland)
    </option>
    <option value="FJ">
        FJ
        (Fiji)
    </option>
    <option value="FK">
        FK
        (Falkland Islands [Islas Malvinas])
    </option>
    <option value="FM">
        FM
        (Micronesia)
    </option>
    <option value="FO">
        FO
        (Faroe Islands)
    </option>
    <option value="FR">
        FR
        (France)
    </option>
    <option value="GA">
        GA
        (Gabon)
    </option>
    <option value="GB">
        GB
        (United Kingdom)
    </option>
    <option value="GD">
        GD
        (Grenada)
    </option>
    <option value="GE">
        GE
        (Georgia)
    </option>
    <option value="GF">
        GF
        (French Guiana)
    </option>
    <option value="GG">
        GG
        (Guernsey)
    </option>
    <option value="GH">
        GH
        (Ghana)
    </option>
    <option value="GI">
        GI
        (Gibraltar)
    </option>
    <option value="GL">
        GL
        (Greenland)
    </option>
    <option value="GM">
        GM
        (Gambia)
    </option>
    <option value="GN">
        GN
        (Guinea)
    </option>
    <option value="GP">
        GP
        (Guadeloupe)
    </option>
    <option value="GQ">
        GQ
        (Equatorial Guinea)
    </option>
    <option value="GR">
        GR
        (Greece)
    </option>
    <option value="GS">
        GS
        (South Georgia and the South Sandwich Islands)
    </option>
    <option value="GT">
        GT
        (Guatemala)
    </option>
    <option value="GU">
        GU
        (Guam)
    </option>
    <option value="GW">
        GW
        (Guinea-Bissau)
    </option>
    <option value="GY">
        GY
        (Guyana)
    </option>
    <option value="HK">
        HK
        (Hong Kong)
    </option>
    <option value="HM">
        HM
        (Heard Island and McDonald Islands)
    </option>
    <option value="HN">
        HN
        (Honduras)
    </option>
    <option value="HR">
        HR
        (Croatia)
    </option>
    <option value="HT">
        HT
        (Haiti)
    </option>
    <option value="HU">
        HU
        (Hungary)
    </option>
    <option value="ID">
        ID
        (Indonesia)
    </option>
    <option value="IE">
        IE
        (Ireland)
    </option>
    <option value="IL">
        IL
        (Israel)
    </option>
    <option value="IM">
        IM
        (Isle of Man)
    </option>
    <option value="IN">
        IN
        (India)
    </option>
    <option value="IO">
        IO
        (British Indian Ocean Territory)
    </option>
    <option value="IQ">
        IQ
        (Iraq)
    </option>
    <option value="IR">
        IR
        (Iran)
    </option>
    <option value="IS">
        IS
        (Iceland)
    </option>
    <option value="IT">
        IT
        (Italy)
    </option>
    <option value="JE">
        JE
        (Jersey)
    </option>
    <option value="JM">
        JM
        (Jamaica)
    </option>
    <option value="JO">
        JO
        (Jordan)
    </option>
    <option value="JP">
        JP
        (Japan)
    </option>
    <option value="KE">
        KE
        (Kenya)
    </option>
    <option value="KG">
        KG
        (Kyrgyzstan)
    </option>
    <option value="KH">
        KH
        (Cambodia)
    </option>
    <option value="KI">
        KI
        (Kiribati)
    </option>
    <option value="KM">
        KM
        (Comoros)
    </option>
    <option value="KN">
        KN
        (Saint Kitts and Nevis)
    </option>
    <option value="KP">
        KP
        (North Korea)
    </option>
    <option value="KR">
        KR
        (South Korea)
    </option>
    <option value="KW">
        KW
        (Kuwait)
    </option>
    <option value="KY">
        KY
        (Cayman Islands)
    </option>
    <option value="KZ">
        KZ
        (Kazakhstan)
    </option>
    <option value="LA">
        LA
        (Laos)
    </option>
    <option value="LB">
        LB
        (Lebanon)
    </option>
    <option value="LC">
        LC
        (Saint Lucia)
    </option>
    <option value="LI">
        LI
        (Liechtenstein)
    </option>
    <option value="LK">
        LK
        (Sri Lanka)
    </option>
    <option value="LR">
        LR
        (Liberia)
    </option>
    <option value="LS">
        LS
        (Lesotho)
    </option>
    <option value="LT">
        LT
        (Lithuania)
    </option>
    <option value="LU">
        LU
        (Luxembourg)
    </option>
    <option value="LV">
        LV
        (Latvia)
    </option>
    <option value="LY">
        LY
        (Libya)
    </option>
    <option value="MA">
        MA
        (Morocco)
    </option>
    <option value="MC">
        MC
        (Monaco)
    </option>
    <option value="MD">
        MD
        (Moldova)
    </option>
    <option value="ME">
        ME
        (Montenegro)
    </option>
    <option value="MF">
        MF
        (Saint Martin)
    </option>
    <option value="MG">
        MG
        (Madagascar)
    </option>
    <option value="MH">
        MH
        (Marshall Islands)
    </option>
    <option value="MK">
        MK
        (Macedonia [FYROM])
    </option>
    <option value="ML">
        ML
        (Mali)
    </option>
    <option value="MM">
        MM
        (Myanmar [Burma])
    </option>
    <option value="MN">
        MN
        (Mongolia)
    </option>
    <option value="MO">
        MO
        (Macau)
    </option>
    <option value="MP">
        MP
        (Northern Mariana Islands)
    </option>
    <option value="MQ">
        MQ
        (Martinique)
    </option>
    <option value="MR">
        MR
        (Mauritania)
    </option>
    <option value="MS">
        MS
        (Montserrat)
    </option>
    <option value="MT">
        MT
        (Malta)
    </option>
    <option value="MU">
        MU
        (Mauritius)
    </option>
    <option value="MV">
        MV
        (Maldives)
    </option>
    <option value="MW">
        MW
        (Malawi)
    </option>
    <option value="MX">
        MX
        (Mexico)
    </option>
    <option value="MY">
        MY
        (Malaysia)
    </option>
    <option value="MZ">
        MZ
        (Mozambique)
    </option>
    <option value="NA">
        NA
        (Namibia)
    </option>
    <option value="NC">
        NC
        (New Caledonia)
    </option>
    <option value="NE">
        NE
        (Niger)
    </option>
    <option value="NF">
        NF
        (Norfolk Island)
    </option>
    <option value="NG">
        NG
        (Nigeria)
    </option>
    <option value="NI">
        NI
        (Nicaragua)
    </option>
    <option value="NL">
        NL
        (Netherlands)
    </option>
    <option value="NO">
        NO
        (Norway)
    </option>
    <option value="NP">
        NP
        (Nepal)
    </option>
    <option value="NR">
        NR
        (Nauru)
    </option>
    <option value="NU">
        NU
        (Niue)
    </option>
    <option value="NZ">
        NZ
        (New Zealand)
    </option>
    <option value="OM">
        OM
        (Oman)
    </option>
    <option value="PA">
        PA
        (Panama)
    </option>
    <option value="PE">
        PE
        (Peru)
    </option>
    <option value="PF">
        PF
        (French Polynesia)
    </option>
    <option value="PG">
        PG
        (Papua New Guinea)
    </option>
    <option value="PH">
        PH
        (Philippines)
    </option>
    <option value="PK">
        PK
        (Pakistan)
    </option>
    <option value="PL">
        PL
        (Poland)
    </option>
    <option value="PM">
        PM
        (Saint Pierre and Miquelon)
    </option>
    <option value="PN">
        PN
        (Pitcairn Islands)
    </option>
    <option value="PR">
        PR
        (Puerto Rico)
    </option>
    <option value="PS">
        PS
        (Palestinian Territories)
    </option>
    <option value="PT">
        PT
        (Portugal)
    </option>
    <option value="PW">
        PW
        (Palau)
    </option>
    <option value="PY">
        PY
        (Paraguay)
    </option>
    <option value="QA">
        QA
        (Qatar)
    </option>
    <option value="RE">
        RE
        (Réunion)
    </option>
    <option value="RO">
        RO
        (Romania)
    </option>
    <option value="RS">
        RS
        (Serbia)
    </option>
    <option value="RU">
        RU
        (Russia)
    </option>
    <option value="RW">
        RW
        (Rwanda)
    </option>
    <option value="SA">
        SA
        (Saudi Arabia)
    </option>
    <option value="SB">
        SB
        (Solomon Islands)
    </option>
    <option value="SC">
        SC
        (Seychelles)
    </option>
    <option value="SD">
        SD
        (Sudan)
    </option>
    <option value="SE">
        SE
        (Sweden)
    </option>
    <option value="SG">
        SG
        (Singapore)
    </option>
    <option value="SH">
        SH
        (Saint Helena)
    </option>
    <option value="SI">
        SI
        (Slovenia)
    </option>
    <option value="SJ">
        SJ
        (Svalbard and Jan Mayen)
    </option>
    <option value="SK">
        SK
        (Slovakia)
    </option>
    <option value="SL">
        SL
        (Sierra Leone)
    </option>
    <option value="SM">
        SM
        (San Marino)
    </option>
    <option value="SN">
        SN
        (Senegal)
    </option>
    <option value="SO">
        SO
        (Somalia)
    </option>
    <option value="SR">
        SR
        (Suriname)
    </option>
    <option value="SS">
        SS

    </option>
    <option value="ST">
        ST
        (São Tomé and Príncipe)
    </option>
    <option value="SV">
        SV
        (El Salvador)
    </option>
    <option value="SX">
        SX

    </option>
    <option value="SY">
        SY
        (Syria)
    </option>
    <option value="SZ">
        SZ
        (Swaziland)
    </option>
    <option value="TC">
        TC
        (Turks and Caicos Islands)
    </option>
    <option value="TD">
        TD
        (Chad)
    </option>
    <option value="TF">
        TF
        (French Southern Territories)
    </option>
    <option value="TG">
        TG
        (Togo)
    </option>
    <option value="TH">
        TH
        (Thailand)
    </option>
    <option value="TJ">
        TJ
        (Tajikistan)
    </option>
    <option value="TK">
        TK
        (Tokelau)
    </option>
    <option value="TL">
        TL
        (East Timor)
    </option>
    <option value="TM">
        TM
        (Turkmenistan)
    </option>
    <option value="TN">
        TN
        (Tunisia)
    </option>
    <option value="TO">
        TO
        (Tonga)
    </option>
    <option value="TR">
        TR
        (Turkey)
    </option>
    <option value="TT">
        TT
        (Trinidad and Tobago)
    </option>
    <option value="TV">
        TV
        (Tuvalu)
    </option>
    <option value="TW">
        TW
        (Taiwan)
    </option>
    <option value="TZ">
        TZ
        (Tanzania)
    </option>
    <option value="UA">
        UA
        (Ukraine)
    </option>
    <option value="UG">
        UG
        (Uganda)
    </option>
    <option value="UM">
        UM
        (U.S. Minor Outlying Islands)
    </option>
    <option value="US">
        US
        (United States)
    </option>
    <option value="UY">
        UY
        (Uruguay)
    </option>
    <option value="UZ">
        UZ
        (Uzbekistan)
    </option>
    <option value="VA">
        VA
        (Vatican City)
    </option>
    <option value="VC">
        VC
        (Saint Vincent and the Grenadines)
    </option>
    <option value="VE">
        VE
        (Venezuela)
    </option>
    <option value="VG">
        VG
        (British Virgin Islands)
    </option>
    <option value="VI">
        VI
        (U.S. Virgin Islands)
    </option>
    <option value="VN">
        VN
        (Vietnam)
    </option>
    <option value="VU">
        VU
        (Vanuatu)
    </option>
    <option value="WF">
        WF
        (Wallis and Futuna)
    </option>
    <option value="WS">
        WS
        (Samoa)
    </option>
    <option value="YE">
        YE
        (Yemen)
    </option>
    <option value="YT">
        YT
        (Mayotte)
    </option>
    <option value="ZA">
        ZA
        (South Africa)
    </option>
    <option value="ZM">
        ZM
        (Zambia)
    </option>
    <option value="ZW">
        ZW
        (Zimbabwe)
    </option>
';
    }

    private function getsoftaApi($package, $service = NULL, $host, $user, $pass, $apitype = NULL) {

        Loader::load(dirname(__FILE__) . DS . "apis" . DS . "softaculous_api.php");
        $api = new Softaculous_API();
        if ($apitype === "addService") {
            $api->login = "https://{$user}:{$pass}@{$host}:2083/frontend/{$this->getAccountThemeonServicesInstall($package, $user)}/softaculous/index.live.php";
        } else {
            $api->login = "https://{$user}:{$pass}@{$host}:2083/frontend/{$this->getAccountTheme($package, $service)}/softaculous/index.live.php";
        }
        return $api;
    }

    private function getAccountThemeonServicesInstall($package, $service) {
        $apiresult = $this->listacctthemes($package, "", "user", "{$service}");
        if (isset($apiresult[0]->theme)) {
            return $apiresult[0]->theme;
        } else {
            return null;
        }
    }

    private function getAccountTheme($package, $service) {
        $row = $this->getModuleRow($package->module_row);
        $api = $this->getcPanelApi($row->meta->accesskey, $row->meta->hostname, $row->meta->username, $row->meta->use_ssl, false);

        $apiresponse = new stdClass();
        $service_fields = $this->serviceFieldsToObject($service->fields);
        $apiresult = $this->listacctthemes($package, $service, "user", "{$service_fields->username}");

        return $apiresult;
    }

    private function listacctthemes($package, $service, $searchtype = null, $search = null) {
        $row = $this->getModuleRow($package->module_row);
        $api = $this->getcPanelApi($row->meta->accesskey, $row->meta->hostname, $row->meta->username, $row->meta->use_ssl, false);
        $theme = null;
        if ($search) {
            $result = $api->listaccts($searchtype, $search);
            if (is_array($result)) {
                foreach ($result as $key => $value) {
                    if (isset($result[$key]->theme)) {
                        $theme = $result[$key]->theme;
                        unset($result[$key]);
                    }
                }
            }
            return $theme;
        }
    }

    private function availableinstallatronscipts($package, $service) {
        $service_fields = $this->serviceFieldsToObject($service->fields);
        $row = $this->getModuleRow($package->module_row);
        $allscript = array(
            "cmd" => "browser"
        );
        $getallps = $this->doInstallatronConnecting($allscript, $row->meta->hostname, $service_fields->username, $service_fields->password);
        $select_option = "";
        foreach ($getallps['data'] as $key => $value) {
            $app_id = explode("_", $getallps['data'][$key]['id']);
            $select_option .= "<option value='{$app_id[0]}'>{$getallps['data'][$key]['name']} - Category: {$getallps['data'][$key]['category']}</option>";
        }

        return $select_option;
    }

    private function doInstallatronConnecting($query, $serverhostname, $serverusername, $serverpass) {
        include_once (dirname(__FILE__) . DS . "apis" . DS . "helper.automation.php");
        $response = _installatron_call("cpanel", $serverhostname, $query, $serverusername, $serverpass);


        return $response;
    }

    private function getAvailableDomains($package, $service) {
        $row = $this->getModuleRow($package->module_row);
        $service_fields = $this->serviceFieldsToObject($service->fields);
        $cpanel = $this->getcPanelApi($row->meta->accesskey, $row->meta->hostname, $row->meta->username, $row->meta->use_ssl, false);
        $get_domains = $cpanel->api2_query($service_fields->username, "Email", "listmaildomains", array());
        $select_option = "";
        foreach ($get_domains->cpanelresult->data as $key => $value) {
            $select_option .= "<option value='{$get_domains->cpanelresult->data[$key]->domain}'>{$get_domains->cpanelresult->data[$key]->domain}</option>";
        }
        return $select_option;
    }

    private function scriptsavailable($package, $service) {
        $row = $this->getModuleRow($package->module_row);
        $service_fields = $this->serviceFieldsToObject($service->fields);
        $api = $this->getsoftaApi($package, $service, $row->meta->hostname, $service_fields->username, $service_fields->password);
        $api->list_scripts();
        $result = $api->scripts;
        $select_option = "";
        foreach ($result as $key => $value) {
            $select_option .= "<option value='{$result[$key]['sid']}'>{$result[$key]['name']} - Type: {$result[$key]['type']}</option>";
        }

        return $select_option;
    }

}
