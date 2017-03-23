'use strict';

module.exports = {

  /**
   * Console log response data for debuging
   * @param  {Response} res
   * @param  {string} route
   * @param  {Object} params
   */
  logHelper: function (res, route, params) {
    console.log('url : ' + route);
    console.log('status : ' + res.status);
    console.log('params : ' + params);
    console.log('headers : ');
    console.log(res.header);
    console.log('body : ');
    console.log(res.body);
  },
};
