<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <title>SURFnet Collaboration Infrastructure EngineBlock</title>
    </head>
    <body>
        <h1>Welcome to SURFnet Collaboration Infrastructure EngineBlock!</h1>
        <p>
            Authentication URLs:
            <ul>
                <li>
                    <a href="/authentication/idp/metadata">
                        /authentication/idp/metadata
                    </a>
                    <br />
                    Metadata for Service Providers, to configure EngineBlock as your Identity Provider
                </li>
                <li>
                    <a href="/authentication/sp/metadata">
                        /authentication/sp/metadata
                    </a>
                    <br />
                    Metadata for Identity Providers, to add EngineBlock as registered Service Provider
                </li>
                <li>
                    <a href="/authentication/proxy/idps-metadata">
                        /authentication/proxy/idps-metadata
                    </a>
                    <br />
                    Metadata with ALL known EngineBlock providers
                </li>
                <li>
                    <a href="/authentication/proxy/idps-metadata/https%3A%2F%2Fmysp.com%2FassertionConsume">
                        /authentication/proxy/idps-metadata/https%3A%2F%2Fmysp.com%2FassertionConsume
                    </a>
                    <br />
                    For Shibboleth Service Providers, get ALL known EngineBlock providers AND (if known)
                    give the metadata for Service Provider with the endpoint:
                    'https://mysp.com/assertionConsume'
                    (Shibboleth expects to see it's self in the metadata document).
                </li>
            </ul>
        </p>
    </body>
</html>