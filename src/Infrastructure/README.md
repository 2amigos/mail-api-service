# Infrastructure Services

These are services that typically talk to external resources and are not part of the primary problem domain. The common 
examples that I see for this are emailing and logging. When trying to categorize an Infrastructure Service, I think you 
can ask yourself the following questions:

If I remove this service, will it affect the execution of my domain model?
If I remove this service, will it affect the execution of my application?

If it will affect your domain model, then it's probably a Domain Service. If, however, it will simply affect your 
application, then it is probably an Infrastructure Service. So for example, if I removed Email Notifications from an 
application, it probably wouldn't affect the core domain model of the application; it would, however, completely break 
the usability of the application - hence it is an Infrastructure Service (and not a Domain Service). 
