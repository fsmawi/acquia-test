// Example of custom settings

module.exports = {

  // Custom browser specifications.
  browser: {
    name: 'chrome',
    width: '1280',
    height: '768',
  },

    // Timeouts
  timeouts: {
    // set in milliseconds
    cucumber: 60 * 1000,     // for a cucumber step
    waitFor: 20 * 1000,      // for all waitForXXX commands
    implicitWait: 20 * 1000, // for driver when searching for elements
    script: 20 * 1000,        // for a script to execute
    saucelabs: {
      // Set in seconds
      maxDuration: 1800,  // Maximum execution time for saucelabs
      commandTimeout: 300, // How long can a command run in the browser
      idleTimeout: 300 // Time to wait before saucelabs triggers a timeout
    }
  },

};
