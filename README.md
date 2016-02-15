Before starting you will need to copy keys-example.php to keys.php and fill in the appropriate credentials.

This project was a quick proof-of-concept and is provided at the request of a few folks. Use at your own risk with the Nest thermostat!

This script fetches information about the thermostat from the Nest API and stores it in Mongodb. Installation and configuration of mongo is outside the scope of this project.

The Nest API limits data storage to 10 days. Appropriate purging of information is left as an exercise to the reader. 

This project uses a very ham-fisted approach to authentication, owing to the fact that is was written for my own personal investigation. Authentication involves manually getting and storing the nest token. Proper implementation of Oauth is also left as an exercise to the reader.

In short, this is not meant to be out-of-the-box ready to execute code so much as a jumping off point for others' projects.
