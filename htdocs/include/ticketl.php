<?php
require_once("pages.php");
require_once("ticketfuncs.php");
require_once("$style/include/style.php");
$act = "tlist";
$ref = pageLinkAct();
pageHeader();

if(isset($_REQUEST["purge"]) && !empty($_REQUEST["sel"]))
{
  $list = array();
  $sel = &$_REQUEST["sel"];
  if(!is_array($sel)) $sel = array($sel);

  // purge immediately
  foreach($sel as $id)
  {
    if(!isTicketId($id)) continue;
    $sql = "SELECT * FROM ticket WHERE id = " . $db->quote($id);
    $DATA = $db->query($sql)->fetch();
    if($DATA === false) continue;

    // check for permissions
    if($DATA["user_id"] != $auth["id"])
      continue;

    // actually purge the ticket
    $list[] = htmlEntUTF8(ticketStr($DATA));
    ticketPurge($DATA, false);
  }

  if(count($list))
    infoMessage(T_("Purged"), $list);
}

// list active tickets
$totalSize = 0;

$sql = 'SELECT * FROM ticket t'
    . ' WHERE user_id = ' . $auth["id"]
    . ' AND from_grant IS NULL'
    . ' ORDER BY time DESC';

?>
<form action="<?php echo $ref; ?>" method="post">
  <table class="sortable" id="tickets">
    <thead>
      <tr>
        <th><input class="element checkbox" type="checkbox" onclick="selectAll(this.checked);"/></th>
        <th data-sort="int"></th>
        <th></th>
        <th></th>
        <th data-sort="string"><?php echo T_("Ticket"); ?></th>
        <th data-sort="int"><?php echo T_("Size"); ?></th>
        <th data-sort="int" class="sorting-desc"><?php echo T_("Date"); ?></th>
        <th data-sort="int"><?php echo T_("Expiration"); ?></th>
      </tr>
    </thead>
    <tbody>
<?php

foreach($db->query($sql) as $DATA)
{
  if(isTicketExpired($DATA)) continue;

  $totalSize += $DATA["size"];
  $our = ($DATA["user_id"] == $auth["id"]);
  $class = "file " . $DATA['id'];
  echo "<tr class=\"$class\">";

  // selection
  echo "<td><input class=\"element checkbox\" type=\"checkbox\" name=\"sel[]\" value=\"" . $DATA['id'] . "\"/></td>";

  // tick
  echo '<td data-sort-value="' . ($DATA["downloads"]? 1: 0) . '">';
  if($DATA["downloads"])
  {
    echo '<img title="' . T_("Successfully downloaded")
      . "\" src=\"$style/static/tick.png\"/>";
  }
  echo "</td>";

  // download
  echo '<td><a href="' . ticketUrl($DATA) . '">'
    . '<img title="' . T_("Download")
    . "\" src=\"$style/static/save.png\"/></a></td>";

  // delete
  echo "<td><a href=\"" . pageLinkAct(array('purge' => null, 'sel' => $DATA['id'])) . "\">"
    . "<img title=\"" . T_("Purge")
    . "\" src=\"$style/static/cross.png\"/></a></td>";

  // name+id+cmt
  $title = $DATA['id'];
  if(!empty($DATA["cmt"]))
    $title .= ": " . $DATA['cmt'];
  echo '<td><a title="' . htmlEntUTF8($title) . '" href="'
    . pageLink('tedit', array('id' => $DATA['id'], 'src' => $act))
    . '" class="filename">' . htmlEntUTF8($DATA["name"])
    . '</a></td>';

  // size/date
  echo '<td data-sort-value="' . $DATA["size"] . '">'
      . humanSize($DATA["size"]) . '</td>';
  echo '<td data-sort-value="' . $DATA["time"] . '">'
      . date($dateFmtShort, $DATA["time"]) . "</td>";

  // expiration
  $expStr = ticketExpiration($DATA, $expVal);
  echo "<td data-sort-value=\"$expVal\">$expStr</td>";

  echo "</tr>";
}

?>
    </tbody>
  </table>

  <ul>
    <li class="buttons">
      <input type="submit" name="reload" value="<?php echo T_("Reload"); ?>"/>
      <input type="reset" value="<?php echo T_("Reset"); ?>"/>
      <input type="button" value="<?php echo T_("Select all"); ?>" onclick="selectAll(true);"/>
      <input type="submit" name="purge" value="<?php echo T_("Purge selected"); ?>"/>
    </li>
  </ul>
</form>

<p><?php printf(T_("Total ticket size: %s"), humanSize($totalSize)); ?></p>

<?php
pageFooter();
