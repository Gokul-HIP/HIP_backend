#CONTEXT
I am building api’s for my healthinpocket general user app,  it has Healthcare providers as organizations in the database each orginization has its own doctors assinged to the hospital, pharmacies,  diagonistic centers, procedures etc.

#ROLE
ACT as a Senior Laravel Developer

#SPECIFICATION
– Response should be of format {status:200, message:”list of all referrals”,data:[], total:450, page:1, page_limit:10}
Total means count of all records, page_limit means total records in each page
- dont alter database column names send it as it is to maintain the data presentation overall the application
– Coding should be easily understandable with short comments
- Create the Controller if the controller does not exist
- We are creating the Apis for general app user so create new controller in  Api/UserApp directory
- Also create a api example in \rest.http file using vs code rest http client
- Do not test the rest.http implementation
- Include page_limit, page  url query parameter where ever applicable
- Default page limit from PAGELIMIT environment variable
- to send response use sendResponse method from app/api_helper.php
- if the api if for creating or updating a entity create the FormRequest file  
- reuse methods in Service available for Model in app/Services directory
- database queries write in Service than controller for reusablity

#PROPERTIES
- Apis should start as api/v1/business-app/

#EXAMPLES
-successfull output
{
    "status": 200,
    "message": "list of all referrals",
    "data": [],
    "total": 450,
    "page": 1,
    "page_limit": 10
}