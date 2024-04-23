<?php $some_result = false; ?>


<ul style="list-style-type:none; padding-left:0px;">
    <?php foreach ($results as $entity_name => $entity_results): ?>

        <?php if (count($entity_results['data']) < 1) {
            continue;
        } else {

            $some_result = true;
        } ?>

        <li>
            <strong>
                <?php echo ucwords(str_replace('_', ' ', $entity_name)) . " (" . count($entity_results['data']) . ")"; ?>
            </strong>

            <ul style="list-style-type:none; margin-top:10px; margin-bottom:20px;padding-left:20px;">

                <?php foreach ($entity_results['data'] as $row): ?>
                    <li style="padding-left:0px;">

                        <?php if ($entity_results['layout_detail_modal'] == DB_BOOL_FALSE): ?>

                            <a href="<?php echo $entity_results['layout_detail_link'] . $row[$entity_name . "_id"]; ?>">

                            <?php else: ?>

                                <a class="js_open_modal"
                                    href="<?php echo $entity_results['layout_detail_link'] . $row[$entity_name . "_id"] . $entity_results['layout_detail_in_modal_params']; ?>">

                                <?php endif; ?>

                                <?php if (!empty($entity_results['preview_print'])): ?>

                                    <?php $string = str_replace_placeholders($entity_results['preview_print'], $row, true, true);

                                    echo $string; ?>

                                <?php else: ?>

                                    <?php foreach ($row as $key => $value): ?>
                                        <?php // Skip id
                                                        if (strpos($key, '_id') !== false) {
                                                            continue;
                                                        } ?>
                                        <?php echo $value; ?>
                                    <?php endforeach; ?>

                                <?php endif; ?>

                            </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </li>
    <?php endforeach; ?>
</ul>

<?php if ($some_result === false): ?>
    <p>No results found</p>
<?php endif; ?>