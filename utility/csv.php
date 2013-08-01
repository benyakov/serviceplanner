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
    private $filebase_index = false;

    public function __construct($iterable, $filebase="",
            $charset="utf-8", $fieldnames=array(), $fieldselection=false) {
        $this->iterable = $iterable;
        $this->filebase = $filebase;
        $this->charset = $charset;
        $this->fieldnames = $fieldnames;
        $this->fieldselection = $fieldselection;
    }

    public $setEncoding = "setCharset";
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
    public function setFilebaseIndex($index) {
        // Set up to pull the filename from this index of the first record
        $this->filebase_index = $index;
    }

    private function sendStart($output, $filename) {
        // Send headers and fieldnames
        header("Content-Type: text/csv; charset={$this->charset}");
        header("Content-Disposition: attachment; filename=\"{$filename}.csv\"");
        if ($this->fieldnames) fputcsv($output, $this->fieldnames);
    }

    public function export() {
        $output = fopen('php://output', 'w');
        if (! $this->filebase_index) {
            $this->sendStart($output, $this->filebase);
        }
        foreach ($this->iterable as $row) {
            if ($this->filebase_index) {
                $this->sendStart($output, $row[$this->filebase_index]);
                $this->filebase_index = false;
            }
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


