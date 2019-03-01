#!/bin/sh
*/1 * * * * curl -o /data/wwwlogs/crontabTest.log http://ssq.xxx.cn/api/contract/conTest
*/3 * * * * curl -o /data/wwwlogs/crontabLock.log http://ssq.xxx.cn/api/contract/conLock
*/3 * * * * curl -o /data/wwwlogs/crontabSign.log http://ssq.xxx.cn/api/contract/conSign
*/3 * * * * curl -o /data/wwwlogs/crontabCreate.log http://ssq.xxx.cn/api/contract/conCreate
*/1 * * * * curl -o /data/wwwlogs/crontabUp.log http://ssq.xxx.cn/api/contract/conUp
*/3 * * * * curl -o /data/wwwlogs/crontabReg.log http://ssq.xxx.cn/api/contract/conReg
