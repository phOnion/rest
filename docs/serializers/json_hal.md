## HAL serializer

Serializes the entity and its child entities as a HAL response. Automatically attempts to populate links with the appropriate 
values from the retrieved data. AFAICT it should be fully compatible with the HAL specification (except maybe for compact
responses, but those are left out on purpose)
