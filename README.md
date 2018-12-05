# Transpose data from decoded POSTed JSON to form data request parameters in Symfony
You may run into the situation where your Symfony app is not necessarily receiving POST requests that contain form data, but contain JSON instead, for example, if your front-end is sending XHR requests as `application/json` rather than actually submitting an HTML form. If you're performing form validation using `symfony/form`, you'll notice that `Form::handleRequest(Request $request)` will completely ignore the JSON in the request body of `application/json` requests.

This listener intercepts POSTed `application/json` requests, decodes the content, and applies each property/value of the decoded data to the request's parameters. Then, in your controller actions,`Form::handleRequest(Request $request)` will behave as expected, as if it were receiving a standard `application/x-www-form-urlencoded` request containing the same data.

## Integrating with a Symfony application
Until I can get a Symfony flex recipe published, you'll need to manually copy the [config file](config/form_data_transposer.yaml) to your project's config directory, or manually copy the content of the config file into your configuration somewhere. I've only needed to use this on a Symfony4 project, so if you need to use it on a previous version, you're more than welcome to make a PR with the appropriate dependency alterations.

## Constructor arguments
The constructor accepts two optional boolean arguments, `$rethrowDecoderException` and `$checkIsXmlHttpRequest`, that can be adjusted in the service definition. `$rethrowDecoderException` determines if an exception caught while decoding the JSON should be rethrown. `$checkIsXmlHttpRequest` determines if `Request::isXmlHttpRequest()` should be called when conditionally checking the request.
