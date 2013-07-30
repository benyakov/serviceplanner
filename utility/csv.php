<? /* Exports a service in csv format
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

class CSVExporter{
    public function __construct($iterable, $filebase,
            $charset="utf-8", $fieldnames=array(), $fieldselection=false) {
        $this->iterable = $iterable;
        $this->filename = $filebase.".csv";
        $this->charset = $charset;
        $this->fieldnames = $fieldnames;
        $this->fieldselection = $fieldselection;
    }

    public function setCharset($charset) {
        $this->charset = $charset;
    }
    public function setFieldnames($fieldnames) {
        $this->fieldnames = $fieldnames;
    }
    public function setFieldselection($fieldselection) {
        $this->fieldselection = $fieldselection;
    }
    public function setFilebase($filebase) {
        $this->filebase = $filebase;
    }

    public function export() {
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename={$_GET['lectionary']}.csv");
        $output = fopen('php://output', 'w');
        if ($this->fieldnames) fputcsv($output, $this->fieldnames);
        foreach ($this->iterable as $row) {
            if ($this->fieldselection) {
                $selectedrow = array();
                foreach ($this->fieldselection as $fieldkey) {
                    $selectedrow[] = $row[$fieldkey];
                }
                fputcsv($output, $selectedrow);
            } else fputcsv($output, $row);
        }
        fclose($output);
    }
}


