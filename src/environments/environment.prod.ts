export const environment = {
  production: true,
  apiEndpoint: 'https://api.pipelines.acquia.com',
  n3Key: '', // user supplied, or from cookies
  n3Secret: '', // user supplied, or from cookies
  headers: {
    'X-ACQUIA-PIPELINES-N3-ENDPOINT': 'https://cloud.acquia.com'
  },
  URL: 'https://pipelines.acquia.com', // redirect url
  auth: { // authentication parameters
    github: {
      oauthEndpoint: '',
      oauthTokenEndpoint: '',
      apiEndpoint: '',
      clientId: '',
      clientSecret: '',
      redirectUrl: '',
      scopes: ''
    }
  },
  amplitudeAPIKey: '59e1dbd5afbc7c7c22c2a144fd7b5732',
  authRedirect: 'https://cloud.acquia.com'
};
