DELETE FROM `cache_stats` WHERE `cache_stats`.`cache_name` = 'defuserclass';
DELETE FROM `cache_stats` WHERE `cache_stats`.`cache_name` = 'emo_dir';
DELETE FROM `cache_stats` WHERE `cache_stats`.`cache_name` = 'exporttype';
DELETE FROM `cache_stats` WHERE `cache_stats`.`cache_name` = 'forumname';
DELETE FROM `cache_stats` WHERE `cache_stats`.`cache_name` = 'forumurl';
DELETE FROM `cache_stats` WHERE `cache_stats`.`cache_name` = 'forum_bin_id';
DELETE FROM `cache_stats` WHERE `cache_stats`.`cache_name` = 'ipb_cookie_prefix';
DELETE FROM `cache_stats` WHERE `cache_stats`.`cache_name` = 'ipb_password_priority';
DELETE FROM `cache_stats` WHERE `cache_stats`.`cache_name` = 'not_found_export_id';
DELETE FROM `cache_stats` WHERE `cache_stats`.`cache_name` = 'use_integration';
DELETE FROM `cache_stats` WHERE `cache_stats`.`cache_name` = 'use_lang';
DELETE FROM `cache_stats` WHERE `cache_stats`.`cache_name` =  'use_sessions';
DELETE FROM `cache_stats` WHERE `cache_stats`.`cache_name` =  'use_wait';
update orbital_blocks set bposition='t' where bposition='c';
update news set comments=(SELECT COUNT(*) FROM comments WHERE type='news' AND toid=news.id) where id=id
update comments set type='rel' where type='';
update notifs set type='relcomments' where type='comments';