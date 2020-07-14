<?php

/**
 * @Author: sink
 * @Date:   2020-07-09 15:37:40
 * @Last Modified by:   sink <21901734@qq.com>
 * @Last Modified time: 2020-07-14 15:06:09
 */
namespace App\Consts;


class CacheKey
{

	const TIMING      = 'timing';
	const TIMING_JOIN = 'timing_join:';

	//compete
	const COMPETE_LAST_USER             = 'compete_last_user';
	const COMPETE_UNDERWAY              = 'compete_underway';
	const COMPETE_AUTO_DATA				= 'compete_auto_data:';
	const COMPETE_AUTO_JOIN             = 'compete_auto_join:';
	const COMPETE_DEAL_TIME             = 'compete_deal_time';
	const COMPETE_RECORD                = 'compete_record:';
	const COMPETE_JOIN_RECORD_LOG       = 'compete_join_record_log:';
	const COMPETE_JOIN_RECORD           = 'compete_join_record:';
	const COMPETE_RULES                 = 'compete_rules';
	const COMPETE_DATA                  = 'compete_data';
	const COMPETE_DETAIL_VISIT			= 'compete_detail_visit';
	const COMPETE_DEAL_TIMING           = 'compete_deal_timing';
	const COMPETE_ENSURE			 	= 'compete_ensure';
	const COMPETE_SET_DEAL_VALUE		= 'compete_set_deal_value';
	const COMPETE_DEAL_VALUE_RECORD     = 'compete_deal_value_record';


	//robot
	const ROBOT_SETING              = 'robot_seting';
	const ROBOT_WAIT_JOIN_LIST      = 'robot_wait_join_list';
	const ROBOT_WAIT_JOIN           = 'robot_wait_join';
	const ROBOT_DEAL_TIME           = 'robot_deal_time';
	const ROBOT_USER                = 'robot_user';
	const ROBOT_COMPETE_JOIN_RECORD = 'robot_compete_join_record:';


	//goods
	const GOODS_INFO = 'goods_info';


	//user
	const USER_INFO                 = 'user_info';
	const USER_BALANCE              = 'user_balance';
	const USER_USE_BALANCE          = 'user_use_balance';
	const USER_JOIN_RECORD          = 'user_join_record';
	const USER_JOIN_RECORD_UNDERWAY = 'user_join_record_underway:';

}

