/**
 * Created by stephen.raghunath on 2/24/17.
 */

const path = require('path');
const moment = require('moment');
const fs = require('fs-extra');
module.exports = function () {
  // Will get called only once before the first feature file being invoked
  this.BeforeFeatures(function (features) {
    global.currentRun = {
      features: features,
    };

    // local global config if available
    let propertiesFilePath = path.join(path.dirname(features[0].getUri()), 'properties.js');
    if (fs.existsSync(propertiesFilePath)) {
      global.currentRun.properties = require(propertiesFilePath);
    } else {
      global.currentRun.properties = false;
    }
  });

  // Will get called before each new feature file is being invoked
  this.BeforeFeature(function (feature) {
    global.currentFeature = {
      feature: feature,
    };

    // check for properties file.
    let propertiesFilePath = path.join(path.dirname(feature.getUri()), path.basename(feature.getUri(), 'feature') + 'properties.js');
    if (fs.existsSync(propertiesFilePath)) {
      global.currentFeature.properties = require(propertiesFilePath);
    } else {
      global.currentFeature.properties = false;
    }
  });

  // Will get called before each scenario is being invoked
  this.BeforeScenario(function (scenario) {
    global.currentScenario = {
      scenario: scenario,
    };

    // Set up a logging folder
    if (process.env.AQTEST_DEBUG) {
      global.CURRENT_SCENARIO_FOLDER = path.join(global.LOG_ROOT || 'test-logs', scenario.getName().replace(/( )/g, ''),
        moment().format('YYYY[_]MM[_]DD[_]X'));
      fs.ensureDirSync(global.CURRENT_SCENARIO_FOLDER);
    }

    // Properties
    if (global.currentFeature.properties && global.currentFeature.properties[scenario.getName()]) {
      global.currentScenario.properties = global.currentFeature.properties[scenario.getName()];
    } else {
      global.currentScenario.properties = false;
    }
  });
};
