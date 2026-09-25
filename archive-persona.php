<?php
/**
 * The members listing at /teams/, and every team page under it
 * (see taxonomy-team.php, which loads this file).
 *
 * Three shapes, one template:
 *
 *   /teams/           everyone still on a team, then the people whose every
 *                     team is in the past
 *   /teams/cot/       that team, then the people who have left it
 *   /teams/former/    nobody of its own — the past members of every team,
 *                     each under their own team's heading
 *
 * The main query is not used. Ordering can come from either the Order box or a
 * per-team field, and a page can hold several lists at once, so each list is
 * fetched by bnwp_people() instead.
 */
if (!defined('ABSPATH')) { exit; }

get_header();

$term     = is_tax('team') ? get_queried_object() : null;
$slug     = $term instanceof WP_Term ? $term->slug : '';
$is_all   = $slug === '';
$is_past  = $slug === bnwp_former_team();

if ($is_all) {
    $heading  = bnwp_home_text('members_title');
    $subtitle = bnwp_home_text('members_sub');
} else {
    $heading  = bnwp_term_name($term);
    $subtitle = bnwp_term_description($term);
}

$teams = bnwp_base_teams();
$past  = bnwp_team_term(bnwp_former_team());
?>

<div class="pagehead pagehead--sunken">
    <div class="wrap">
        <h1><?php echo esc_html($heading); ?></h1>
        <?php if ($subtitle) : ?>
            <p style="color:var(--ink-soft);max-width:62ch;margin:0 0 1.25rem;"><?php echo esc_html($subtitle); ?></p>
        <?php endif; ?>

        <?php if ($teams) :
            $all_link = get_post_type_archive_link('persona');
            // Folded away on a phone, where five chips ate the top of the
            // screen before a single face appeared. Opened again by app.js,
            // which is also what closes it below the desktop breakpoint.
            ?>
            <details class="teamnav" open>
                <summary class="teamnav__toggle">
                    <span><?php echo esc_html(bnwp_home_text('teamnav_label')); ?></span>
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                </summary>
                <nav class="teamnav__list" aria-label="<?php echo esc_attr(bnwp_home_text('teamnav_label')); ?>">
                    <a class="chip <?php echo $is_all ? 'chip--live' : ''; ?>"
                       href="<?php echo esc_url(bnwp_lang_arg($all_link ? $all_link : home_url('/teams/'))); ?>"
                       <?php echo $is_all ? 'aria-current="page"' : ''; ?>><?php echo esc_html(bnwp_home_text('teamnav_all')); ?></a>
                    <?php foreach ($teams as $t) :
                        $link = get_term_link($t);
                        if (is_wp_error($link)) { continue; }
                        $active = $slug === $t->slug;
                    ?>
                        <a class="chip <?php echo $active ? 'chip--live' : ''; ?>"
                           href="<?php echo esc_url(bnwp_lang_arg($link)); ?>"
                           <?php echo $active ? 'aria-current="page"' : ''; ?>><?php echo esc_html(bnwp_term_name($t)); ?></a>
                    <?php endforeach; ?>
                    <?php if ($past && bnwp_former_people()) :
                        $past_link = get_term_link($past);
                        if (!is_wp_error($past_link)) : ?>
                        <a class="chip <?php echo $is_past ? 'chip--live' : ''; ?>"
                           href="<?php echo esc_url(bnwp_lang_arg($past_link)); ?>"
                           <?php echo $is_past ? 'aria-current="page"' : ''; ?>><?php echo esc_html(bnwp_term_name($past)); ?></a>
                    <?php endif; endif; ?>
                </nav>
            </details>
        <?php endif; ?>
    </div>
</div>

<?php
/*
 * What this page is made of: a list of [team slug for roles, heading or null,
 * people]. A null heading means the section carries the page's own title and
 * needs no second one.
 */
$sections = array();

if ($is_all) {
    $sections[] = array('', null, bnwp_active_people());
} elseif ($is_past) {
    // Split by the team each person left, because "former" on its own says
    // nothing about what they were.
    foreach ($teams as $t) {
        $former = bnwp_former_slug($t);
        $people = bnwp_people($former);
        if ($people) {
            $sections[] = array($former, bnwp_term_name(bnwp_team_term($former)), $people);
        }
    }
} else {
    $sections[] = array($slug, null, bnwp_people($slug));
    if (!bnwp_team_is_former($slug)) {
        $former = bnwp_former_slug($slug);
        $people = bnwp_people($former);
        if ($people) {
            $sections[] = array($former, bnwp_term_name(bnwp_team_term($former)), $people);
        }
    }
}

$rendered = 0;
foreach ($sections as $i => $section) :
    list($section_team, $section_head, $section_people) = $section;
    if (!$section_people) { continue; }
    $rendered++;
    ?>
    <div class="section<?php echo $i % 2 ? ' section--sunken' : ''; ?>">
        <div class="wrap">
            <?php if ($section_head) : ?>
                <div class="section__head"><div><h2><?php echo esc_html($section_head); ?></h2></div></div>
            <?php endif; ?>
            <?php bnwp_people_grid($section_people, $section_team); ?>
        </div>
    </div>
<?php endforeach; ?>

<?php
// The all-members page keeps its past members at the bottom, under their own
// heading and with no roles: a role belongs to a team, and theirs is over.
$former_all = $is_all ? bnwp_former_people() : array();
if ($former_all) : ?>
<div class="section section--sunken">
    <div class="wrap">
        <div class="section__head">
            <div>
                <h2><?php echo esc_html(bnwp_home_text('former_title')); ?></h2>
                <p><?php echo esc_html(bnwp_home_text('former_sub')); ?></p>
            </div>
        </div>
        <div class="grid grid--4" data-stagger>
            <?php foreach ($former_all as $person) : ?>
                <a class="person reveal" href="<?php echo esc_url(bnwp_person_url($person->ID)); ?>">
                    <?php bnwp_image(get_post_meta($person->ID, '_bnwp_img', true), array(
                        'w' => 176, 'h' => 176, 'class' => 'person__avatar', 'alt' => get_the_title($person->ID),
                    )); ?>
                    <span class="person__name"><?php echo esc_html(get_the_title($person->ID)); ?></span>
                    <?php $handle = get_post_meta($person->ID, '_bnwp_username', true); ?>
                    <?php if ($handle) : ?><span class="person__handle">@<?php echo esc_html($handle); ?></span><?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php elseif (!$rendered) : ?>
<div class="section">
    <div class="wrap">
        <p class="notice"><?php echo esc_html(bnwp_text('কোনো সদস্য পাওয়া যায়নি।', 'No members found.')); ?></p>
    </div>
</div>
<?php endif; ?>

<?php get_footer(); ?>
