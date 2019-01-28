<?php get_header(); 

$website = get_userdata(4);
basicPrint($website->data->user_url);

?>

<section id="singleApp"></section>

<?php get_footer(); ?>