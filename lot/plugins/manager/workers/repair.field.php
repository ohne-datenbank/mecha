<?php echo $messages; ?>
<form class="form-<?php echo $id ? 'repair' : 'ignite'; ?> form-field" id="form-<?php echo $id ? 'repair' : 'ignite'; ?>" action="<?php echo $config->url_current; ?>" method="post">
  <?php echo Form::hidden('token', $token); $page = $file; ?>
  <?php include __DIR__ . DS . 'unit' . DS . 'form' . DS . 'field' . DS . 'title.php'; ?>
  <?php include __DIR__ . DS . 'unit' . DS . 'form' . DS . 'key.php'; ?>
  <?php include __DIR__ . DS . 'unit' . DS . 'form' . DS . 'field' . DS . 'type.php'; ?>
  <?php include __DIR__ . DS . 'unit' . DS . 'form' . DS . 'field' . DS . 'scope[].php'; ?>
  <?php include __DIR__ . DS . 'unit' . DS . 'form' . DS . 'placeholder.php'; ?>
  <?php include __DIR__ . DS . 'unit' . DS . 'form' . DS . 'value.textarea.php'; ?>
  <?php include __DIR__ . DS . 'unit' . DS . 'form' . DS . 'description.text.php'; ?>
  <div class="grid-group">
    <span class="grid span-1"></span>
    <span class="grid span-5">
      <?php if($id): ?>
      <?php echo Jot::button('action', $speak->update); ?>
      <?php echo Jot::btn('destruct', $speak->delete, $config->manager->slug . '/field/kill/key:' . $id); ?>
      <?php else: ?>
      <?php echo Jot::button('construct', $speak->create); ?>
      <?php endif; ?>
    </span>
  </div>
</form>
<script>
(function(w, d) {
    var form = d.getElementById('form-<?php echo $id ? 'repair' : 'ignite'; ?>'),
        type = form.type,
        value = form.value,
        placeholder = form.placeholder,
        holder = "";
    function onchange(v) {
        // `input[type="text"] < .grid < .grid-group`
        placeholder.parentNode.parentNode.style.display = v[0].match(/^[ceost]$/) ? "" : 'none';
        if (v[0] === 'o') {
            holder = '<?php echo strtolower($speak->key) . S . ' ' . $speak->value; ?>';
        } else if (v[0] === 'f') {
            holder = '<?php echo IMAGE_EXT; ?>';
        } else if (v[0] === 'b') {
            holder = '1';
        } else {
            holder = "";
        }
        value.placeholder = holder;
    }
    onchange(type.value);
    type.onchange = function() {
        onchange(this.value);
    };
})(window, document);
</script>
<hr>
<?php echo Guardian::wizard($segment); ?>