require('colors');
'use strict';


module.exports = {

  /**
   * Console log response data for debuging
   * @param  {Response} res
   * @param  {string} route
   * @param  {Object} params
   */
  logAPICall: function (res, route, params) {
    console.log('url : '.yellow + route);
    console.log('status : '.yellow + res.status);
    console.log('params : '.yellow + params);
    console.log('headers : '.yellow);
    console.log(res.header);
    console.log('body : '.yellow);
    console.log(res.body);
  },
};
