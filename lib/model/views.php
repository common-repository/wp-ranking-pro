<?php

class WP_Ranking_PRO_Model_Views
{
    public function count($post_id, $remote_addr, $http_user_agent, $http_referer) {
        # normalize spaces (for to tsv)
        # and trim spaces
        $remote_addr     = trim(preg_replace('/\s+/', ' ', $remote_addr));
        $http_user_agent = trim(preg_replace('/\s+/', ' ', $http_user_agent));
        $http_referer    = trim(preg_replace('/\s+/', ' ', $http_referer));

        global $wpdb;

        $ua_string    = $http_user_agent;
        $ua_is_mobile = $this->util_is_mobile($ua_string);

        $wpdb->query($wpdb->prepare("
            INSERT IGNORE INTO `{$wpdb->prefix}wpr_http_user_agents`
                (`string`, `sha1_hash`, `is_mobile`)
            VALUES
                (%s, SHA1(%s), %d)
        ",
            $ua_string,
            $ua_string,
            $ua_is_mobile
        ));

        $ua_id = $wpdb->get_var($wpdb->prepare("
            SELECT `id` FROM `{$wpdb->prefix}wpr_http_user_agents` WHERE `sha1_hash` = SHA1(%s)
        ",
            $ua_string
        ));

        $wpdb->insert("{$wpdb->prefix}wpr_views", array(
            'accessed_at'     => current_time('mysql'),
            'post_id'         => $post_id,
            'remote_addr'     => $remote_addr,
            'http_user_agent' => $ua_id,
            'http_referer'    => $http_referer,
            'is_logged_in'    => is_user_logged_in(),
        ), array(
            '%s',
            '%d',
            '%s',
            '%d',
            '%s',
            '%d',
        ));

        if ($this->util_is_remote_addr_excluded($remote_addr)) {
            return;
        }

        if ($this->util_is_http_referer_excluded($http_referer)) {
            return;
        }

        if ($this->util_is_http_user_agent_excluded($ua_string)) {
            return;
        }

        $wpdb->query($wpdb->prepare("
            INSERT INTO `{$wpdb->prefix}wpr_views_per_day`
                (`accessed_on`, `post_id`, `views_pc`, `views_pc_only_visitors`, `views_mobile`, `views_mobile_only_visitors`)
            VALUES
                (NOW(), %d, %d, %d, %d, %d)
            ON DUPLICATE KEY UPDATE
                `views_pc`                   = `views_pc`                   + %d,
                `views_pc_only_visitors`     = `views_pc_only_visitors`     + %d,
                `views_mobile`               = `views_mobile`               + %d,
                `views_mobile_only_visitors` = `views_mobile_only_visitors` + %d
        ",
            $post_id,

            intval(!$ua_is_mobile),
            intval(!$ua_is_mobile && !is_user_logged_in()),
            intval( $ua_is_mobile),
            intval( $ua_is_mobile && !is_user_logged_in()),

            intval(!$ua_is_mobile),
            intval(!$ua_is_mobile && !is_user_logged_in()),
            intval( $ua_is_mobile),
            intval( $ua_is_mobile && !is_user_logged_in())
        ));
    }

    private function util_is_mobile($ua_string) {
        $as_mobiles = preg_split('/[\r\n]+/', get_option('wpr_as_mobile_http_user_agent', ''), null, PREG_SPLIT_NO_EMPTY);

        foreach ($as_mobiles as $as_mobile) {
            if (stripos($ua_string, $as_mobile) !== false) {
                return 1;
            }
        }

        return 0;
    }

    private function util_is_remote_addr_excluded($remote_addr) {
        $excluded_remote_addrs = preg_split('/[\r\n]+/', get_option('wpr_exclusion_remote_addrs', ''), null, PREG_SPLIT_NO_EMPTY);

        foreach ($excluded_remote_addrs as $excluded_remote_addr) {
            if ($excluded_remote_addr == $remote_addr) {
                return 1;
            }
        }

        return 0;
    }

    private function util_is_http_referer_excluded($http_referer) {
        $excluded_http_referers = preg_split('/[\r\n]+/', get_option('wpr_exclusion_http_referers', ''), null, PREG_SPLIT_NO_EMPTY);

        foreach ($excluded_http_referers as $excluded_http_referer) {
            # prefix search
            if (strpos($http_referer, $excluded_http_referer) === 0) {
                return 1;
            }
        }

        return 0;
    }

    private function util_is_http_user_agent_excluded($ua_string) {
        $excluded_http_user_agents = preg_split('/[\r\n]+/', get_option('wpr_exclusion_http_user_agent', ''), null, PREG_SPLIT_NO_EMPTY);

        foreach ($excluded_http_user_agents as $excluded_http_user_agent) {
            # prefix search
            if (strpos($ua_string, $excluded_http_user_agent) !== false) {
                return 1;
            }
        }

        return 0;
    }

    public function rebuild_cache() {
        global $wpdb;

        # clear cache {
        $wpdb->query("DELETE FROM `{$wpdb->prefix}wpr_views_per_day` WHERE accessed_on >= (SELECT CAST(MIN(accessed_at) AS DATE) AS accessed_at FROM `{$wpdb->prefix}wpr_views`)");
        $wpdb->query(<<<SQL
            DELETE FROM
                `{$wpdb->options}`
            WHERE
                `option_name` LIKE '\\_transient\\_wpr-%'
                    OR
                `option_name` LIKE '\\_transient\\_timeout\\_wpr-%'
SQL
        );
        # }

        # update user-agent table {
        $as_mobile = preg_split('/[\r\n]+/', get_option('wpr_as_mobile_http_user_agent', ''), null, PREG_SPLIT_NO_EMPTY);

        $is_mobile = count($as_mobile)
            ? 'IF(' . implode(' OR ', array_fill(1, count($as_mobile), "`string` LIKE CONCAT('%%', REPLACE(REPLACE(%s, '%%', '\\%%'), '_', '\\_'), '%%')")) . ', 1, 0)'
            : '0' ;

        $wpdb->query($wpdb->prepare("
            UPDATE
                `{$wpdb->prefix}wpr_http_user_agents`
            SET
                is_mobile = $is_mobile
        ",
            $as_mobile
        ));
        # }

        # rebuild cache {
        $excluded_remote_addrs = preg_split('/[\r\n]+/', get_option('wpr_exclusion_remote_addrs', '') , null, PREG_SPLIT_NO_EMPTY);
        $excluded_remote_addrs_cond = count($excluded_remote_addrs)
            ? 'v.remote_addr NOT IN (' . implode(', ', array_fill(1, count($excluded_remote_addrs), '%s')) . ')'
            : false ;

        $excluded_http_referers = preg_split('/[\r\n]+/', get_option('wpr_exclusion_http_referers', ''), null, PREG_SPLIT_NO_EMPTY);
        $excluded_http_referers_cond = count($excluded_http_referers)
            ? '(' . implode(' OR ', array_fill(1, count($excluded_http_referers), "v.http_referer NOT LIKE CONCAT(REPLACE(REPLACE(%s, '%%', '\\%%'), '_', '\\_'), '%%')")) . ')'
            : false ;

        $excluded_http_user_agent = preg_split('/[\r\n]+/', get_option('wpr_exclusion_http_user_agent', ''), null, PREG_SPLIT_NO_EMPTY);
        $excluded_http_user_agent_cond = count($excluded_http_user_agent)
            ? '(' . implode(' AND ', array_fill(1, count($excluded_http_user_agent), "ua.string NOT LIKE CONCAT('%%', REPLACE(REPLACE(%s, '%%', '\\%%'), '_', '\\_'), '%%')")) . ')'
            : false ;

        $wpdb->query($wpdb->prepare("
            INSERT INTO `{$wpdb->prefix}wpr_views_per_day`
                (`accessed_on`, `post_id`, `views_pc`, `views_pc_only_visitors`, `views_mobile`, `views_mobile_only_visitors`)
            SELECT
                CAST(`accessed_at` AS DATE) AS `accessed_on`,
                v.post_id,
                SUM(!ua.is_mobile),
                SUM(!ua.is_mobile AND !v.is_logged_in),
                SUM( ua.is_mobile),
                SUM( ua.is_mobile AND !v.is_logged_in)
            FROM
                `{$wpdb->prefix}wpr_views` AS `v`
                    JOIN `{$wpdb->prefix}wpr_http_user_agents` AS `ua` ON v.http_user_agent = ua.id
            WHERE
                " . implode(' AND ', array_filter(array('1', $excluded_remote_addrs_cond, $excluded_http_referers_cond, $excluded_http_user_agent_cond))) . "
            GROUP BY
                `accessed_on`, v.post_id
            ",
                array_merge(
                    $excluded_remote_addrs,
                    $excluded_http_referers,
                    $excluded_http_user_agent
                )
            ));
    }

    public function reset_views() {
        global $wpdb;

        $wpdb->query("TRUNCATE TABLE `{$wpdb->prefix}wpr_views`");
        $wpdb->query("TRUNCATE TABLE `{$wpdb->prefix}wpr_views_per_day`");
        # wpr_rankingについては削除は不要
        $wpdb->query("TRUNCATE TABLE `{$wpdb->prefix}wpr_http_user_agents`");
    }

    public function cleanlog_views() {
        $next = $this->_cleanlog_views();

        # イベントが登録されていない場合は設定
        if(!wp_next_scheduled('wpr_scheduled_cleanlog')) {
            # WordPress breaks PHP time-zone
            $next -= (get_option('gmt_offset') * 3600);

            switch (get_option('wpr_cleanlog_cycle', 0)) {
                case 0: # 毎日
                    $schedule = 'daily';
                    break;

                case 1: # 毎週 (WordPressの「週のはじまり」に準ずる)
                    $schedule  = 'weekly';
                    break;

                case 2: # 毎月
                    $schedule  = 'monthly';
                    break;
            }
            
            wp_schedule_event($next, $schedule, 'wpr_scheduled_cleanlog');
        }
    }

    private function _cleanlog_views() {
        $cleanlog_cycle     = get_option('wpr_cleanlog_cycle', 0);
        $cleanlog_time	    = get_option('wpr_cleanlog_time' , 0);
        $cleanlog_keeptimes = intval(get_option('wpr_cleanlog_keep_times', 0));
        if ($cleanlog_keeptimes < 0)  {$cleanlog_keeptimes = 0;}

        switch ($cleanlog_cycle) {
            case 0: # 毎日
                $end      = date_i18n('Y-m-d', strtotime('today -1 day'));
                if ($cleanlog_keeptimes > 0) {
                    $end  = date_i18n('Y-m-d', strtotime($end . ' -' . $cleanlog_keeptimes . ' day'));
                }
                $next     = strtotime('today +1 day ' . $cleanlog_time . ' hour');
				if ($next-(get_option('gmt_offset') * 3600) <= time()) {
					$next += 86400;
				}

                break;

            case 1: # 毎週 (WordPressの「週のはじまり」に準ずる)
                $this_week           = get_weekstartend(date_i18n('Y-m-d'));
                $this_week_first_day = date_i18n('Y-m-d', $this_week['start']);
                $this_week_last_day  = date_i18n('Y-m-d', $this_week['end']);

                $end      = date_i18n('Y-m-d', strtotime($this_week_first_day . ' -1 day'));
                if ($cleanlog_keeptimes > 0) {
                    $end  = date_i18n('Y-m-d', strtotime($end . ' -' . $cleanlog_keeptimes . ' week'));
                }
                $next     = strtotime($this_week_last_day . ' +1 day ' . $cleanlog_time . ' hour');

                break;

            case 2: # 毎月
                $this_month_first_day = date_i18n('Y-m-1');
                $this_month_last_day  = date_i18n('Y-m-t');

                $end      = date_i18n('Y-m-d', strtotime($this_month_first_day . ' -1 day'));
                if ($cleanlog_keeptimes > 0) {
                    $end  = date_i18n('Y-m-t', strtotime($end . ' -' . $cleanlog_keeptimes . ' month'));
                }
                $next     = strtotime($this_month_last_day . ' +1 day ' . $cleanlog_time . ' hour');

                break;
        }

        # 指定日0時以前のアクセスログを削除する。
        global $wpdb;

        $wpdb->query($wpdb->prepare("DELETE FROM `{$wpdb->prefix}wpr_views` WHERE accessed_at <= %s", $end.' 23:59:59'));

        return $next;
    }
}
