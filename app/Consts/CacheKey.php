<?php

/**
 * @Author: sink
 * @Date:   2020-07-09 15:37:40
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-10 18:21:31
 */
namespace App\Consts;


class CacheKey
{

	const TIMING      = 'timing';
	const TIMING_JOIN = 'timing_join:';

	//compete
	const COMPETE_LAST_USER             = 'compete_last_user';
	const COMPETE_UNDERWAY              = 'compete_underway';
	const COMPETE_AUTO_JOIN             = 'compete_auto_join:';
	const COMPETE_DEAL_TIME             = 'compete_deal_time';
	const COMPETE_RECORD                = 'compete_record';
	const COMPETE_RECORD_INDEX          = 'compete_record_index';
	const COMPETE_JOIN_RECODE_LOG       = 'compete_join_recode_log';
	const COMPETE_JOIN_RECODE           = 'compete_join_recode';
	const COMPETE_JOIN_RECODE_INDEX     = 'compete_join_recode_index';
	const COMPETE_RULES                 = 'compete_rules';
	const COMPETE_DATA                  = 'compete_data';


	//robot
	const ROBOT_SETING         = 'robot_seting';
	const ROBOT_WAIT_JOIN_LIST = 'robot_wait_join_list';
	const ROBOT_TIMING_JOIN    = 'robot_timing_join:';
	const ROBOT_DEAL_TIME      = 'robot_deal_time';
	const ROBOT_USER           = 'robot_user';
	const ROBOT_WAIT_JOIN      = 'robot_wait_join';


	//goods
	const GOODS_INFO = 'goods_info';


	//user
	const USER_INFO = 'user_info';

}

