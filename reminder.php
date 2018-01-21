<? /* Provide reminders based on special tags.

    Copyright (C) 2017 Jesse Jacobsen

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

$thisdir = dirname(__FILE__);
require("{$thisdir}/init.php");
$query = findFlagsUpcoming("Remind", 6);
$host = $_SERVER['HTTP_HOST'];
while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    $m=<<<EOM
You or someone on your behalf has set up a reminder for a church service
planned at {$row['occurrence']} on {$row['date']}. This is your reminder, and
it is the only one you will receive for this particular occurrence of this
service until next week.

You can see and print the details of the service on the page linked here:
{$protocol}://{$host}{$serverdir}/print.php?id={$row['service']}. To print, simply
use your web browser's print function. (Usually Ctrl-P on a Linux/Windows PC or
Cmd-P on a Mac)

<a href="{$protocol}://{$host}{$serverdir}/print.php?id={$row['service']}" title="Click here">Here's that address formatted as a web link</a>

To set or remove these automated reminders, you can create or remove "Remind"
flags at the service planner at {$protocol}://{$host}{$serverdir}.
Enter your exact login name as the flag value.  It's the name you use to sign
into the service planner.

God bless you and your church family through His service of word and sacrament.
EOM;
    $mailresult = mail($row['email'],
        "Service Planner Reminder for Upcoming Service: {$row['date']} at {$_SERVER['HTTP_HOST']}",
        $m,
        "From: noreply@{$_SERVER['HTTP_HOST']}",
        "-fnoreply@{$_SERVER['HTTP_HOST']}"
    );
    if (! $mailresult) {
        echo "Mail didn't send.";
    } else {
        echo "Mail sent.";
    }
}
