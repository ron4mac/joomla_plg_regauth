# joomla_plg_regauth
A user plugin that causes an authorization code to be required on the registration form.  

Users can automatically be assigned to different groups depending on the authorization code used.  

Functions with standard Joomla user registration.  

### Useage
Install the plugin in Joomla as is done for other extensions.  

Configure the plugin by entering one or more authorization codes. Each code can optionally be configured to create a user in the selected Joomla user group(s). If no user group(s) is selected for a code, the user will be assigned to the Joomla default group.  

Make sure to enable the plugin.

### Registration by invitation
Version 1.5.2+ of this plugin works with the companion module, [mod_regauth](https://github.com/ron4mac/joomla_mod_regauth), to recognize registration invitation links. The invitation link will automatically apply the embedded authorization code.
