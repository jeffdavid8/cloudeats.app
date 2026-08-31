




### 1. The Service Model (service.model.php)
Purpose: To define the "Offering"
Storage: Extends Storage (/includes/models/storage.model.php) and lives in the memory_anchors table. 
Content (JSON): Stores the custom tier definitions (Name, Price, Limits) that the Service Owner creates. 
Tenant Lock: Uses the Owner's architect_id to ensure their Service is theirs alone. 

### 2. The Service Definition Interface (The "Admin" View)
Function: A UI for the Service Owner to "Stitch" together their tiers.
Data: Feeds into the content field of the Service model. 
Action: "Save Service" creates/updates the service anchor.

### 3. The Purchase Workflow (The "Stitch")
Display: A public-facing view that pulls the tier data from the Service model. 
Gateway: User pays via Stripe, PayPal, or Bank Transfer. 
Creation: Upon payment success, call Membership::create($data). 
The Link: The new membership anchor includes the service_uuid in its JSON content so we know exactly which "Service" it belongs to.
