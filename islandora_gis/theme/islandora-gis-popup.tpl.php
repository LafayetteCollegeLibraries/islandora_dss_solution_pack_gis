<?php

  /**
   * @file Template for Overlay Popup Content
   * @author griffinj@lafayette.edu
   *
   */

?>
<div class="islandora-gis-popup islandora">

  <div class="islandora-gis-popup-thumb">

    <?php print $islandora_object_tn ?>
  </div>

  <fieldset class="islandora-gis-metadata">

    <div class="fieldset-wrapper">
      <dl class="islandora-inline-metadata islandora-gis-fields">
        <?php $row_field = 0; ?>
        <?php foreach($islandora_metadata_fields as $key => $value): ?>
          <dt class="<?php print $value['class']; ?><?php print $row_field == 0 ? ' first' : ''; ?>">
            <?php print $value['label']; ?>
          </dt>
          <dd class="<?php print $value['class']; ?><?php print $row_field == 0 ? ' first' : ''; ?>">
            <?php print $value['value']; ?>
          </dd>
          <?php $row_field++; ?>
        <?php endforeach; ?>
      </dl>
    </div>
  </fieldset>
</div>
