
$(document).ready(function (e) {
    $("form").keypress(function (e) {
        //Enter key
        if (e.which == 13) {
            return false;
        }
    });
});

function doAjaxWithData(url, title, data) {
    $.ajax({
        type: "GET",
        url: url,
        data: data,
        beforeSend: function () {
            $('#global_modal').modal('show');
            $("#global_modal .global_modal_title").html(title);
            $("#global_modal .modal-body").remove();
            $("<div class='modal-body'><i class='fa fa-spinner fa-spin'></i></div>").insertAfter("#global_modal .modal-header");
        },
        success: function (e) {
            $("#global_modal .modal-body").remove();
            $("#global_modal .modal-footer").remove();
            $('#global_modal').modal('show');
            $(e).insertAfter("#global_modal .modal-header");
        },
        error: function () {
            $("<div class='modal-body'>Internal Error Occured, Request Could Not Be Processed</div>").insertAfter("#global_modal .modal-header");
        }

    });
    $('#global_modal').on('hide.bs.modal', function () {
        $("#global_modal .global_modal_title").html('');
        $("<div class='modal-body'><i class='fa fa-spinner fa-spin'></i></div>").insertAfter("#global_modal .modal-header");
        window.location.href = document.URL;
    });
}
;
function doAjaxRmv(url, title) {
    $.ajax({
        type: "GET",
        url: url,
        beforeSend: function () {
            $('#global_modal').modal('show');
            $("#global_modal .global_modal_title").html(title);
            $("#global_modal .modal-body").remove();
            $("<div class='modal-body'><i class='fa fa-spinner fa-spin'></i></div>").insertAfter("#global_modal .modal-header");
        },
        success: function (e) {
            $("#global_modal .modal-body").remove();
            $("#global_modal .modal-footer").remove();
            $('#global_modal').modal('show');
            $(e).insertAfter("#global_modal .modal-header");
        },
        error: function () {
            $("<div class='modal-body'>Internal Error Occured, Request Could Not Be Processed</div>").insertAfter("#global_modal .modal-header");
        }

    });
    $('#global_modal').on('hide.bs.modal', function () {
        $("#global_modal .global_modal_title").html('');
        $("<div class='modal-body'><i class='fa fa-spinner fa-spin'></i></div>").insertAfter("#global_modal .modal-header");
        window.location.href = document.URL;
    });
}
;
function doAjaxPost(url, data) {
    $.ajax({
        type: "POST",
        url: url,
        data: data,
        beforeSend: function () {
            $(".div_response").html('<i class="fa fa-spinner fa-spin"></i>');
        },
        success: function (e) {
            $(".div_response").html(e);
        },
        error: function () {
            $(".div_response").html("Internal Error Occured, Request Could Not Be Processed");
        }

    });

}
;
function doAjaxGet(url, data) {
    $.ajax({
        type: "GET",
        url: url,
        data: data,
        beforeSend: function () {
            $(".new_div").html('<i class="fa fa-spinner fa-spin"></i>');
        },
        success: function (e) {
            $(".new_div").html(e);
        },
        error: function () {
            $(".new_div").html("Internal Error Occured, Request Could Not Be Processed");
        }

    });
    $('#global_modal').on('hide.bs.modal', function () {
        $("#global_modal .global_modal_title").html('');
        $("#global_modal .modal-body").html("<i class='fa fa-spinner fa-spin'></i>");
        window.location.href = document.URL;
    });
}
;
function doAjax(url, title) {
    $.ajax({
        type: "GET",
        url: url,
        beforeSend: function () {
            $('#global_modal').modal('show');
            $("#global_modal .global_modal_title").html(title);
            $("#global_modal .modal-body").remove();
            $("<div class='modal-body'><i class='fa fa-spinner fa-spin'></i></div>").insertAfter("#global_modal .modal-header");
        },
        success: function (e) {
            $('#global_modal').modal('show');
            $("#global_modal .modal-body").html(e);
        },
        error: function () {
            $("#global_modal .modal-body").html("Internal Error Occured, Request Could Not Be Processed");
        }

    });
    $('#global_modal').on('hide.bs.modal', function () {
        $("#global_modal .global_modal_title").html('');
        $("#global_modal .modal-body").html('<i class="fa fa-spinner fa-spin"></i>');
    });
}
;