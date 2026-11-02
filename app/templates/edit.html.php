
<?php

$lib = ['conflict' => "An entry can EITHER belong to a series or identify a NEW series",
'retire' => "Operation forbidden; delete the record instead",
'reassign' => "This series id is in use",
'impostor' => "Title should be unique; with the exception that a series and an episode may share the same title"];

if(isset($lib[$key])): ?>
    <h2><?= $lib[$key]; ?></h2>
<?php endif; ?>

<form action="<?= $action; ?>" method="post" class="edit">
        <fieldset>
                <input type="hidden" id="pk" name="pk" value="<?= $file->id ?? ''; ?>">
                <label for="title">title</label>
                <input type="text" name="data[title]" id="title" value="<?= $file->title ?? ''; ?>" required pattern="[-A-Za-z\d._\s',:]+" maxlength="125">
                <label for="recdate">recording date</label>
                <input type="datetime-local" name="data[recdate]" id="recdate" placeholder="YYYY-MM-DD" required maxlength="10" value="<?= $file->recdate ?? ''; ?>">
                <label for="series_id">series_id</label>
                <input type="number" name="data[series_id]" id="series_id" step="1" min="0" max="<?= $max; ?>" value="<?= $file->series_id ?? null; ?>" <?= $_series; ?>/>
               
                <?php
                if ($options !== []) { ?>
                 <label for="assign">assign</label>
                <select title="Select" name="assign" id="assign" <?= $_assign; ?>>
                    <option value="0">Select One</option>
                    <?php
                    foreach ($options as $k => $v) :
                        $selected = '';
                        $selected = $k == $target ? 'selected' : '';
                        ?>
                        <option <?= $selected; ?> value="<?= $k; ?>"><?= $v; ?></option>
                        <?php endforeach;  ?>
                        </select>
                        <?php
                        } ?>
                        </fieldset>
                        <input type="submit" value="cancel" name="cancel" formnovalidate>
                        <input type="submit" value="submit" name="submit">

                    </form>
                    <div class="remplacer">
                </div>