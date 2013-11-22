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
function propersForm($dayname) {
    $dbh = new DBConnection();
    $dbp = $dbh->getPrefix();
    $q = $dbh->prepare("SELECT pr.color, pr.theme, pr.introit, pr.note,
        pr.gradual, l.lesson1, l.lesson2, l.gospel, l.psalm, l.s2lesson,
        l.s2gospel, l.s3lesson, l.s3gospel, l.id, l.lectionary, l.hymnabc,
        l.hymn
        FROM `{$dbp}churchyear_propers` AS pr
        LEFT OUTER JOIN `{$dbp}churchyear_lessons` AS l
            ON (pr.dayname = l.dayname)
        LEFT OUTER JOIN `{$dbp}churchyear_synonyms` AS s
            ON (pr.dayname = s.synonym)
        WHERE s.canonical = ?
        ORDER BY l.lectionary");
    $q->bindValue(1, $dayname);
    if (! $q->execute()) {
        die(array_pop($q->errorInfo()));
        $pdata = array("color"=>"", "theme"=>"", "introit"=>"", "gradual"=>"",
            "note"=>"", "lesson1"=>"", "lesson2"=>"", "gospel"=>"",
            "psalm"=>"", "s2lesson"=>"", "s2gospel"=>"", "s3lesson"=>"",
            "s3gospel"=>"", "id"=>0, "lectionary"=>"",
            "hymnabc"=>"", "hymn"=>"");
    } else {
        $pdata = ($q->fetchAll(PDO::FETCH_ASSOC));
    }
    $lectionaries = array();
    foreach ($pdata as $p) {
        $lectionaries[$p['lectionary']] = 1;
    }
    $lectionaries = array_keys($lectionaries);
    sort($lectionaries);
    $q = $dbh->prepare("SELECT c.class, c.collect, i.lectionary, i.id
        FROM `{$dbp}churchyear_collect_index` AS i
        LEFT OUTER JOIN `{$dbp}churchyear_collects` AS c ON (i.id = c.id)
        WHERE i.dayname = ?
        ORDER BY i.lectionary");
    if (! $q->execute(array($dayname))) {
        die(array_pop($q->errorInfo()));
        $cdata = array();
    } else {
        $cdata = ($q->fetchAll(PDO::FETCH_ASSOC));
    }
    ob_start();
?>
    <div id="tabs">
    <ul>
        <li><a href="#propers-tab"><span>Basic Propers</span></a></li> <?
        $histloc = array_search('historic', $lectionaries);
        if ($histloc !== false) { ?>
            <li><a href="#historic-tab"><span>Historic</span></a></li> <?
            array_splice($lectionaries, $histloc, 1);
        }
        foreach ($lectionaries as $l) { ?>
        <li><a href="#<?=$l?>-tab"><span><?=$l?></span></a></li>
        <? } ?>
        <li><a href="#newpropers-tab"><span>New Propers</span></a></li>
    </ul>
    <div id="propers-tab">
    <form class="propersform" method="post">
    <div class="propersbox">
    <input type="hidden" name="propers" id="propers" value="<?=$dayname?>">
    <div class="formblock"><label for="color">Color</label><br>
    <input type="text" value="<?=$pdata[0]['color']?>" name="color"></div>
    <div class="formblock"><label for="theme">Theme</label><br>
    <input type="text" value="<?=$pdata[0]['theme']?>" name="theme"></div>
    <div class="formblock fullwidth"><label for="note">Note</label><br>
    <textarea name="note"><?=$pdata[0]['note']?></textarea><br></div>
    <div class="formblock fullwidth"><label for="introit">Introit</label><br>
    <textarea name="introit"><?=$pdata[0]['introit']?></textarea></div>
    <div class="formblock fullwidth"><label for="gradual">Gradual</label><br>
    <textarea name="gradual"><?=$pdata[0]['gradual']?></textarea></div>
    <button type="submit">Submit</button>
    <button type="reset">Reset</button>
    </div>
    </form>
    </div>
    <?
    foreach ($pdata as $lset) {
        $id = $lset['id'];
        if ($lset['lectionary'] == "historic") {
    ?>
    <div id="historic-tab">
    <div class="propers">
    <form class="lessons propersform" method="post">
    <div class="propersbox">
    <input type="hidden" name="lessons" value="<?=$id?>">
    <input type="hidden" name="lessontype" value="historic">
    <div class="formblock"><label for="l1">Lesson 1</label><br>
    <input type="text" value="<?=$lset['lesson1']?>" name="l1"></div>
    <div class="formblock"><label for="l2">Lesson 2</label><br>
    <input type="text" value="<?=$lset['lesson2']?>" name="l2"></div>
    <div class="formblock"><label for="go">Gospel</label><br>
    <input type="text" value="<?=$lset['gospel']?>" name="go"></div>
    <div class="formblock"><label for="ps">Psalm</label><br>
    <input type="text" value="<?=$lset['psalm']?>" name="ps"></div>
    <div class="formblock"><label for="s2l">Series 2 Lesson</label><br>
    <input type="text" value="<?=$lset['s2lesson']?>" name="s2l"></div>
    <div class="formblock"><label for="s2go">Series 2 Gospel</label><br>
    <input type="text" value="<?=$lset['s2gospel']?>" name="s2go"></div>
    <div class="formblock"><label for="s3l">Series 3 Lesson</label><br>
    <input type="text" value="<?=$lset['s3lesson']?>" name="s3l"></div>
    <div class="formblock"><label for="s3go">Series 3 Gospel</label><br>
    <input type="text" value="<?=$lset['s3gospel']?>" name="s3go"></div><br>
    <button type="submit" class="submit-lessons">Submit</button>
    <button type="reset">Reset</button>
    </div>
    </form>
    <form class="collects" method="post">
    <div class="propersbox"> <?
    foreach ($cdata as $cset) {
        $cid = $cset['id'];
        if ($cset['lectionary'] == $lset['lectionary']) { ?>
            <div class="formblock fullwidth">
            <label for="collect-<?=$cid?>"><?=$cset['class']?></label>
            <a href="#" class="delete-collect" data-id="<?=$cid?>">Delete</a>
            <br>
            <textarea name="collect-<?=$cid?>"><?=$cset['collect']?></textarea>
            </div> <?
        }
    } ?>
    <a href="#" class="add-collect"
        data-lectionary="<?=$lset['lectionary']?>">New Collect</a><br>
    <button type="submit" class="submit-collects">Submit</button>
    <button type="reset">Reset</button>
    </div>
    </form>
    </div>
    </div>
    <? } else { ?>
    <div id="<?=$lset["lectionary"]?>-tab">
    <div class="propers">
    <form class="lessons propersform" method="post">
    <div class="propersbox">
    <? if (strpos($lset['lectionary'], 'ilcw') !== 0) { ?>
    <a href="#" class="delete-these-propers"
    data-id="<?=$id?>">Delete these from <?=$lset['lectionary']?>.</a><br>
    <?}?>
    <input type="hidden" name="lessons" value="<?=$id?>">
    <input type="hidden" name="lessontype" value="ilcw">
    <div class="formblock"><label for="l1">Lesson 1</label><br>
    <input type="text" value="<?=$lset['lesson1']?>" name="l1"></div>
    <div class="formblock"><label for="l2">Lesson 2</label><br>
    <input type="text" value="<?=$lset['lesson2']?>" name="l2"></div>
    <div class="formblock"><label for="go">Gospel</label><br>
    <input type="text" value="<?=$lset['gospel']?>" name="go"></div>
    <div class="formblock"><label for="ps">Psalm</label><br>
    <input type="text" value="<?=$lset['psalm']?>" name="ps"></div>
    <div class="formblock"><label for="habc">General Hymn</label><br>
    <input type="text" value="<?=$lset['hymnabc']?>" name="habc"></div>
    <div class="formblock"><label for="hymn">Series Hymn</label><br>
    <input type="text" value="<?=$lset['hymn']?>" name="hymn"></div><br>
    <button type="submit" class="submit-lessons">Submit</button>
    <button type="reset">Reset</button>
    </div>
    </form>
    <form class="collects" method="post">
    <div class="propersbox"> <?
    foreach ($cdata as $cset) {
        $cid = $cset['id'];
        if ($cset['lectionary'] == $lset['lectionary']) { ?>
            <div class="formblock fullwidth">
            <label for="collect-<?=$cid?>"><?=$cset['class']?></label>
            <a href="#" class="delete-collect" data-id="<?=$cid?>">Delete</a>
            <br>
            <textarea name="collect-<?=$cid?>"><?=$cset['collect']?></textarea>
            </div> <?
        }
    }?>
    <a href="#" class="add-collect"
        data-lectionary="<?=$lset['lectionary']?>">New Collect</a><br>
    <button type="submit" class="submit-collects">Submit</button>
    <button type="reset">Reset</button>
    </div>
    </form>
    </div>
    </div> <?
    }} ?>
    <div class="hiddentemplate" id="newpropers-tab">
    <div class="propers">
    <form class="lessons" id="newlessons" method="post">
    <div class="propersbox">
    <input type="hidden" name="lessons" value="New">
    <input type="hidden" name="dayname" value="<?=$dayname?>">
    <textarea class="datalist"
    id="lectionaries-for-dayname"><?=implode("\n", $lectionaries)?></textarea>
    <div class="formblock"><label for="lectionary">Lectionary (letters & numbers only)</label><br>
    <input type="text" value="" name="lectionary" id="new-lectionary" required pattern="[A-Za-z0-9]+"></div>
    <div class="formblock"><label for="l1">Lesson 1</label><br>
    <input type="text" value="" name="l1"></div>
    <div class="formblock"><label for="l2">Lesson 2</label><br>
    <input type="text" value="" name="l2"></div>
    <div class="formblock"><label for="go">Gospel</label><br>
    <input type="text" value="" name="go"></div>
    <div class="formblock"><label for="ps">Psalm</label><br>
    <input type="text" value="" name="ps"></div>
    <div class="formblock"><label for="habc">General Hymn</label><br>
    <input type="text" value="" name="habc"></div>
    <div class="formblock"><label for="hymn">Series Hymn</label><br>
    <input type="text" value="" name="hymn"></div><br>
    <button type="submit" class="submit-lessons">Submit</button>
    <button type="reset">Reset</button>
    </div>
    </form>
    </div>
    </div>
    </div>
    <script type="text/javascript">
        setupPropersDialog();
    </script>
    <?
    return ob_get_clean();
}
