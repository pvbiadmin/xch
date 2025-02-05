<?php

namespace BPL\Ajax\Ajaxer\Fast_Track;

require_once 'bpl/ajax/ajaxer/jquery_number.php';

use function BPL\Ajax\Ajaxer\Jquery_Number\main as jquery_number;

/**
 * @param $user_id
 *
 * @return string
 *
 * @since version
 */
function main($user_id): string
{
    $ajax_script = <<<'SCRIPT'
    (function ($) {
        // Function to save entries per page to localStorage
        function saveEntriesPerPage() {
            var entriesPerPage = $('#datatablesSimple_length select').val();
            localStorage.setItem('entriesPerPage', entriesPerPage);
        }

        // Function to restore entries per page from localStorage
        function restoreEntriesPerPage() {
            var savedEntries = localStorage.getItem('entriesPerPage');
            if (savedEntries) {
                $('#datatablesSimple_length select').val(savedEntries);
                // Trigger change event to apply the pagination
                $('#datatablesSimple_length select').trigger('change');
            }
        }

        $(document).ready(function () {
            // Restore entries per page when page loads
            restoreEntriesPerPage();

            // Save entries per page when changed
            $('#datatablesSimple_length select').on('change', function() {
                saveEntriesPerPage();
            });

            $("#fast_track").click(function () {
                var data = {
                    "action": "fast_track",
                    "input": $("#fast_track_input").val(),
                    "user_id": %d
                };
                data = $(this).serialize() + "&" + $.param(data);
                $.ajax({
                    type: "post",
                    dataType: "json",
                    url: "bpl/ajax/action.php",
                    data: data,
                    success: function (data) {
                        $("#fast_track_input").val("");
                        $("#fast_track").attr("disabled", true);

                        var balance = data["balance"] === undefined ? "" : data["balance"];
                        var principal = data["principal"] === undefined ? "" : data["principal"];
                        var interest = data["interest"] === undefined ? "" : data["interest"];
                        var success_fast_track = data["success_fast_track"] === undefined ? "" : data["success_fast_track"];
                        var error_fast_track = data["error_fast_track"] === undefined ? "" : data["error_fast_track"];

                        var usd_bal_now_user_cl = $(".usd_bal_now_user");
                        var fast_track_principal_cl = $(".fast_track_principal");
                        var fast_track_value_last_cl = $(".fast_track_value_last");
                        var success_fast_track_cl = $(".success_fast_track");
                        var error_fast_track_cl = $(".error_fast_track");

                        if (balance !== "") {
                            $(usd_bal_now_user_cl[0]).html($.number(balance, 2));
                        }
                        
                        if (principal !== "") {
                            $(fast_track_principal_cl[0]).html($.number(principal, 2));
                        }
                        
                        if (interest !== "") {
                            $(fast_track_value_last_cl[0]).html($.number(interest, 2));
                        }
                        
                        if (success_fast_track !== "") {
                            $(success_fast_track_cl[0]).html(success_fast_track).fadeIn().delay(3000).fadeOut(function() {
                                // AJAX reload of the table and dynamic content
                                $.ajax({
                                    url: window.location.href,
                                    method: 'GET',
                                    success: function(response) {
                                        // Update specific dynamic sections
                                        var $newContent = $(response);
                                        
                                        // Update table
                                        $('#datatablesSimple tbody').html($newContent.find('#datatablesSimple tbody').html());
                                        
                                        // Update shares section
                                        $('.fast_track_value_last').html($newContent.find('.fast_track_value_last').html());
                                        
                                        // Update principal section
                                        $('.fast_track_principal').html($newContent.find('.fast_track_principal').html());
                                        
                                        // Restore entries per page
                                        restoreEntriesPerPage();
                                        
                                        // Re-enable the button
                                        $("#fast_track").attr("disabled", false);
                                    }
                                });
                            });
                        }
                                               
                        if (error_fast_track !== "") {
                            $(error_fast_track_cl[0]).html(error_fast_track).fadeIn().delay(3000).fadeOut();
                        }
                    }
                });

                return false;
            });
        });
    })(jQuery);
    SCRIPT;

    // Use sprintf to insert user_id into the script
    $str = '<script>' . sprintf($ajax_script, $user_id) . '</script>';

    $str .= jquery_number();

    return $str;
}