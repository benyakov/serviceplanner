<? /* Show services without modification interface
    Copyright (C) 2012 Jesse Jacobsen

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

    Send feedback or donations to: Jesse Jacobsen <jmatjac@gmail.com>

    Mailed donation may be sent to:
    Bethany Lutheran Church
    2323 E. 12th St.
    The Dalles, OR 97058
    USA
 */
require("./init.php");
$this_script = $_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'] ;
?>
<!DOCTYPE html>
<html lang="en">
<?=html_head("Service Planning Records")?>
<body>
    <script type="text/javascript">
        function setupMasterButtons() {
            $('#thisweek').click(function(evt) {
                evt.preventDefault();
                scrollTarget("now");
                var dest1 = $("html").scrollTop();
                $("html").scrollTop(dest1-75);
            });
            if (! Modernizr.inputtypes.date) {
                $("#lowdate").datepicker({showOn:"both",
                    numberOfMonths: [1,2],
                    stepMonths: 2});
                $("#highdate").datepicker({showOn:"both",
                    numberOfMonths: [1,2],
                    stepMonths: 2});
            };
            $('#allfuture').change(function() {
                if (this.checked) {
                    $('#highdate').prop('disabled', true)
                        .addClass('disabled-input');
                } else {
                    $('#highdate').prop('disabled', false)
                        .removeClass('disabled-input');
                }
            });
            if ($('#allfuture').is(':checked')) {
                $('#highdate').prop('disabled', true)
                    .addClass('disabled-input');

            }
        }
        $(document).ready(function() {
            contractAllListings('records-listing');
            setupListingExpansion();
            setupFlags();
            $.appear('.service-flags', {"interval":0.1, force_process: "t"});
            setupFilterForm();
            setupMasterButtons();
        });
    </script>
<?  pageHeader();
    siteTabs();?>
    <div id="content-container">
    <div class="quicklinks"><a id="thisweek" href="#now">Jump to This Week</a></div>
    <?
    include("records-table.php");
    ?>
    </div>
<div id="dialog"></div>
</body>
</html>
