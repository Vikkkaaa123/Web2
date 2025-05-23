<table>
<tr>
  <th>Заголовок 1</th>
  <th>Заголовок 2</th>
  <th>Удалить</th>
</tr>
<?php
foreach ($c['admin'] as $id => $row) {
?>
  
  <tr>
    <td><?php print($row[0]); ?></td>
    <td><?php print($row[1]); ?></td>
    <td>
      <form action="/admin/<?php print($id); ?>" method="POST">
        <input type="submit" value="Удалить">
      </form>
    </td>
  </tr>
<?php  
}
?>
</table>
