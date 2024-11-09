"use strict";

$("#customFileEg1").change(function () {
    readURL(this);
});

$("#generalSection").click(function() {
    $("#passwordSection").removeClass("active");
    $("#generalSection").addClass("active");
    $('html, body').animate({
        scrollTop: $("#generalDiv").offset().top
    }, 2000);
});

$("#passwordSection").click(function() {
    $("#generalSection").removeClass("active");
    $("#passwordSection").addClass("active");
    $('html, body').animate({
        scrollTop: $("#passwordDiv").offset().top
    }, 2000);
});
