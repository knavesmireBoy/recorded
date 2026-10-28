
<?php

$lib = ['conflict' => "An entry can EITHER belong to a series or identify a NEW series"];

if(isset($lib[$key])): ?>
    <h2><?= $lib[$key]; ?></h2>
<?php endif; ?>

<form action="<?= $action; ?>" method="post" class="">
        <fieldset>
                <input type="hidden" id="pk" name="pk" value="<?= $file->id ?? ''; ?>">
                <label for="title">title</label>
                <input type="text" name="data[title]" id="title" value="<?= $file->title ?? ''; ?>" required pattern="[-A-Za-z\d._\s]+" maxlength="125">
                <label for="recdate">recording date</label>
                <input type="date" name="data[recdate]" id="recdate" placeholder="YYYY-MM-DD" required maxlength="10" value="<?= $file->recdate ?? ''; ?>">
                <label for="series_id">series_id</label>
                <input type="number" name="data[series_id]" id="series_id" step="1" min="0" max="92" value="<?= $file->series_id ?? null; ?>" />
               
                <?php
                if ($options !== []) { ?>
                 <label for="assign">assign</label>
                <select title="Select One" name="assign" id="assign">
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
                        <input type="submit" value="submit" name="submit">
                    </form>
                    <div class="remplacer">
                    <a href="/tvlist/add">Add</a>
                    <a href="<?= $exit ?>">Exit</a></div>
                </div>