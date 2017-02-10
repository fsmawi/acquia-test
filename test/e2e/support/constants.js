module.exports = {

  PIPELINES_URL: process.env.PIPELINES_URL || 'http://localhost:4200',

  TIMEOUT_SHORT: 1000 * (parseInt(process.env.TIMEOUT_SHORT) || 10),
  TIMEOUT_LONG: 1000 * (parseInt(process.env.TIMEOUT_LONG) || 150),
};
