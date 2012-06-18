<? /* Show populated form for the propers of the given dayname
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
    $dayname = $_GET['propers'];
    if (! $auth) {
        echo json_encode(array(false));
        exit(0);
    }
    $q = $dbh->prepare("SELECT pr.color, pr.theme, pr.introit, pr.note,
        l.lesson1, l.lesson2, l.gospel, l.psalm, l.s2lesson, l.s3gospel,
        l.s3lesson, l.s3gospel, l.id, l.lectionary, l.hymnabc, l.hymn
        FROM `{$dbp}churchyear_propers` AS pr
        LEFT OUTER JOIN `{$dbp}churchyear_lessons` AS l
            ON (pr.dayname = l.dayname)
            WHERE pr.dayname = ?
        ORDER BY l.lectionary");
    if (! $q->execute(array($dayname]))) {
        die(array_pop($q->errorInfo()));
        $pdata = array("color"=>"", "theme"=>"", "introit"=>"",
            "note"=>"", "lesson1"=>"", "lesson2"=>"", "gospel"=>"",
            "psalm"=>"", "s2lesson"=>"", "s2gospel"=>"", "s3lesson"=>"",
            "s3gospel"=>"", "id"=>0, "lectionary"=>"",
            "hymnabc"=>"", "hymn"=>"");
    } else {
        $pdata = ($q->fetchAll(PDO::FETCH_ASSOC));
    }
    $q = $dbh->prepare("SELECT i.lectionary, c.class, c.collect, c.id
        FROM `{$dbp}churchyear_collect_index` AS i
        JOIN `{$dbp}churchyear_collects` AS c
            ON (i.id = c.id)
        WHERE i.dayname = ?
        ORDER BY i.lectionary, c.class");
    if (! $q->execute(array($dayname]))) {
        die(array_pop($q->errorInfo()));
        $cdata = array();
    } else {
        $cdata = ($q->fetchAll(PDO::FETCH_ASSOC));
    }
    ob_start();
?>
    <form id="propersform" method="post">
    <input type="hidden" name="propers" id="propers" value="<?=$dayname]?>">
    <div class="formblock"><label for="color">Color</label><br>
    <input type="text" value="<?=$pdata[0]['color']?>" name="color"></div>
    <div class="formblock"><label for="theme">Theme</label><br>
    <input type="text" value="<?=$pdata[0]['theme']?>" name="theme"></div>
    <div class="formblock fullwidth"><label for="note">Note</label><br>
    <textarea name="note"><?=$pdata[0]['note']?></textarea><br></div>
    <div class="formblock fullwidth"><label for="introit">Introit</label><br>
    <textarea name="introit"><?=$pdata[0]['introit']?></textarea></div>
    <div id="accordion">
    <? $i = 1;
    foreach ($pdata as $lset) {
        $id = $lset['id'];
        if (! $lset['lectionary'] == "historic") {
    ?>
    <h3 class="propers-<?=$id?>">
        <a href="#"><?=strtoupper($lset['lectionary'])?></a></h3>
    <div class="propers-<?=$id?>">
    <a href="#" class="delete-these-propers"
        data-id="<?=$id?>">Delete these propers</a>
    <div class="propersbox">
    <input type="hidden" name="lessons-<?=$i?>" value="<?=$id?>">
    <input type="hidden" name="lessontype" value="historic">
    <div class="formblock"><label for="l1-<?=$id?>">Lesson 1</label><br>
    <input type="text" value="<?=$lset['lesson1']?>" name="l1-<?=$id?>"></div>
    <div class="formblock"><label for="l2-<?=$id?>">Lesson 2</label><br>
    <input type="text" value="<?=$lset['lesson2']?>" name="l2-<?=$id?>"></div>
    <div class="formblock"><label for="go-<?=$id?>">Gospel</label><br>
    <input type="text" value="<?=$lset['gospel']?>" name="go-<?=$id?>"></div>
    <div class="formblock"><label for="ps-<?=$id?>">Psalm</label><br>
    <input type="text" value="<?=$lset['psalm']?>" name="ps-<?=$id?>"></div>
    <div class="formblock"><label for="s2l-<?=$id?>">Series 2 Lesson</label><br>
    <input type="text" value="<?=$lset['s2lesson']?>" name="s2l-<?=$id?>"></div>
    <div class="formblock"><label for="s2go-<?=$id?>">Series 2 Gospel</label><br>
    <input type="text" value="<?=$lset['s2gospel']?>" name="s2go-<?=$id?>"></div>
    <div class="formblock"><label for="s3l-<?=$id?>">Series 3 Lesson</label><br>
    <input type="text" value="<?=$lset['s3lesson']?>" name="s3l-<?=$id?>"></div>
    <div class="formblock"><label for="s3go-<?=$id?>">Series 3 Gospel</label><br>
    <input type="text" value="<?=$lset['s3gospel']?>" name="s3go-<?=$id?>"></div>
    </div>
    <div class="propersbox"> <?
    foreach ($cdata as $cset) {
        $cid = $cset['id'];
        if ($cset['lectionary'] = $lset['lectionary']) { ?>
            <div class="formblock fullwidth">
            <label for="collect-<?=$cid?>"><?=$cset['class']?></label>
            <a href="#" class="delete-collect" data-id="<?=$cid?>">Delete</a>
            <br>
            <textarea name="collect-<?=$cid?>"><?=$cset['collect']?></textarea>
            </div> <?
        }
    } ?>
    <a href="#" class="add-collect"
        data-lectionary="<?=$lset['lectionary']?>">New Collect</a>
    </div>
    </div>
    <? } else { ?>
    <h3 class="propers-<?=$id?>">
        <a href="#"><?=strtoupper($lset['lectionary'])?></a></h3>
    <div class="propers-<?=$id?>">
    <a href="#" class="delete-these-propers"
        data-id="<?=$id?>">Delete these propers</a>
    <div class="propersbox">
    <input type="hidden" name="lessons-<?=$i?>" value="<?=$id?>">
    <input type="hidden" name="lessontype" value="ilcw">
    <div class="formblock"><label for="l1-<?=$id?>">Lesson 1</label><br>
    <input type="text" value="<?=$lset['lesson1']?>" name="l1-<?=$id?>"></div>
    <div class="formblock"><label for="l2-<?=$id?>">Lesson 2</label><br>
    <input type="text" value="<?=$lset['lesson2']?>" name="l2-<?=$id?>"></div>
    <div class="formblock"><label for="go-<?=$id?>">Gospel</label><br>
    <input type="text" value="<?=$lset['gospel']?>" name="go-<?=$id?>"></div>
    <div class="formblock"><label for="ps-<?=$id?>">Psalm</label><br>
    <input type="text" value="<?=$lset['psalm']?>" name="ps-<?=$id?>"></div>
    <div class="formblock"><label for="habc-<?=$id?>">General Hymn</label><br>
    <input type="text" value="<?=$lset['hymnabc']?>" name="habc-<?=$id?>"></div>
    <div class="formblock"><label for="hymn-<?=$id?>">Series Hymn</label><br>
    <input type="text" value="<?=$lset['hymn']?>" name="hymn-<?=$id?>"></div>
    </div>
    <div class="propersbox"> <?
    foreach ($cdata as $cset) {
        $cid = $cset['id'];
        if ($cset['lectionary'] = $lset['lectionary']) { ?>
            <div class="formblock fullwidth">
            <label for="collect-<?=$cid?>"><?=$cset['class']?></label>
            <a href="#" class="delete-collect" data-id="<?=$cid?>">Delete</a>
            <br>
            <textarea name="collect-<?=$cid?>"><?=$cset['collect']?></textarea>
            </div> <?
        }
    }?>
    <a href="#" class="add-collect"
        data-lectionary="<?=$lset['lectionary']?>">New Collect</a>
    </div>
    </div> <?
    }
    $i++;
    } $i++; ?>
    </div>
    <div class="hiddentemplate" id="propers-template" data-identifier="<?=$i?>">
    <h3 class="new-propers-{{id}}"><a href="#">New Propers</a></h3>
    <div class="new-propers-{{id}}">
    <a href="#" class="abort-new-propers"
        data-id="{{id}}">Abort New Propers</a>
    <div class="propersbox">
    <input type="hidden" name="lessons-<?=$i?>" value="{{id}}">
    <div class="formblock"><label for="lectionary-{{id}}">Lectionary</label><br>
    <input type="text" value="" name="lectionary-{{id}}" required></div>
    <div class="formblock"><label for="l1-{{id}}">Lesson 1</label><br>
    <input type="text" value="" name="l1-{{id}}"></div>
    <div class="formblock"><label for="l2-{{id}}">Lesson 2</label><br>
    <input type="text" value="" name="l2-{{id}}"></div>
    <div class="formblock"><label for="go-{{id}}">Gospel</label><br>
    <input type="text" value="" name="go-{{id}}"></div>
    <div class="formblock"><label for="ps-{{id}}">Psalm</label><br>
    <input type="text" value="" name="ps-{{id}}"></div>
    <div class="formblock"><label for="habc-{{id}}">General Hymn</label><br>
    <input type="text" value="" name="habc-{{id}}"></div>
    <div class="formblock"><label for="hymn-{{id}}">Series Hymn</label><br>
    <input type="text" value="" name="hymn-{{id}}"></div>
    </div>
    <div class="propersbox">
    <a href="#" class="add-collect"
        data-lectionary="">New Collect</a>
    </div>
    </div>
    </div>
    <button type="submit" id="submit">Submit</button>
    <button type="reset">Reset</button>
    <button id="addpropers">Add Propers</button>
    </form>
    <script type="text/javascript">
        setupPropersDialog();
    </script>
<?
    echo json_encode(array(true, ob_get_clean()));
    exit(0);
