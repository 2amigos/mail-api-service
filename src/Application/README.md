# Application Services

Sometimes referred to as "Workflow Services" or "User Cases", these services orchestrate the steps required to fulfill 
the commands imposed by the client. While these services shouldn't have "business logic" in them, they can certainly 
carry out a number of steps required to fulfill an application need. Typically, the Application Service layer will make 
calls to the Infrastructure Services, Domain Services, and Domain Entities in order to get the job done.

The Application Service can only be called by the Controller. Since the Application Service orchestrates application 
workflow, it would make no sense for them to be called by the Domain Service (or even by themselves) - a workflow has 
only one single starting point; if an Application Service could be called by other entities within the domain model, it 
would imply that a workflow has an indeterminate number of starting points.

