<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<base href="<?php echo URL::to('/'); ?>">
<title>Task Created</title>
</head>
<body  style="color: #333333; font-family: Arial, sans-serif; font-size: 14px; line-height: 1.429">
<table id="background-table" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; background-color: #f5f5f5; border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt" bgcolor="#f5f5f5">
  <!-- header here -->
  <tbody>
    <tr>
      <td id="header-pattern-container" style="padding: 0px; border-collapse: collapse; padding: 10px 20px"><table id="header-pattern" cellspacing="0" cellpadding="0" border="0" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt">
          <tbody>
            <tr>
              <td id="header-avatar-image-container" valign="top" style="padding: 0px; border-collapse: collapse; vertical-align: top; width: 32px; padding-right: 8px" width="32">
                <img id="header-avatar-image" class="image_fix" src="{{$data['UserProfileImage']}}" height="32" width="32" border="0" style="border-radius: 3px; vertical-align: top">
              </td>
              <td id="header-text-container" valign="middle" style="padding: 0px; border-collapse: collapse; vertical-align: middle; font-family: Arial, sans-serif; font-size: 14px; line-height: 20px; mso-line-height-rule: exactly; mso-text-raise: 1px"><a class="user-hover"   href="#" style="color:#8cc8c6;; color: #3b73af; text-decoration: none">{{$data['TitleHeading']}}</a></td>
            </tr>
          </tbody>
        </table></td>
    </tr>
    <tr>
      <td id="email-content-container" style="padding: 0px; border-collapse: collapse; padding: 0 20px"><table id="email-content-table" cellspacing="0" cellpadding="0" border="0" width="100%" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; border-spacing: 0; border-collapse: separate">
          <tbody>
            <tr> 
              <!-- there needs to be content in the cell for it to render in some clients -->
              <td class="email-content-rounded-top mobile-expand" style="padding: 0px; border-collapse: collapse; color: #ffffff; padding: 0 15px 0 16px; height: 15px; background-color: #ffffff; border-left: 1px solid #cccccc; border-top: 1px solid #cccccc; border-right: 1px solid #cccccc; border-bottom: 0; border-top-right-radius: 5px; border-top-left-radius: 5px; height: 10px; line-height: 10px; padding: 0 15px 0 16px; mso-line-height-rule: exactly" height="10" bgcolor="#ffffff">&nbsp;</td>
            </tr>
            <tr>
              <td class="email-content-main mobile-expand " style="padding: 0px; border-collapse: collapse; border-left: 1px solid #cccccc; border-right: 1px solid #cccccc; border-top: 0; border-bottom: 0; padding: 0 15px 0 16px; background-color: #ffffff" bgcolor="#ffffff"><table class="page-title-pattern" cellspacing="0" cellpadding="0" border="0" width="100%" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt">
                  <tbody>
                    <tr>
                      <td style="vertical-align: top;; padding: 0px; border-collapse: collapse; padding-right: 5px; font-size: 20px; line-height: 30px; mso-line-height-rule: exactly" class="page-title-pattern-header-container"><span class="page-title-pattern-header" style="font-family: Arial, sans-serif; padding: 0; font-size: 20px; line-height: 30px; mso-text-raise: 2px; mso-line-height-rule: exactly; vertical-align: middle"> <a href="{{$data['TaskBoardUrl']}}" style="color: #3b73af; text-decoration: none">{{$data['Subject_task']}}</a> </span></td>
                    </tr>
                  </tbody>
                </table></td>
            </tr>
            <tr>
              <td class="email-content-main mobile-expand  issue-description-container" style="padding: 0px; border-collapse: collapse; border-left: 1px solid #cccccc; border-right: 1px solid #cccccc; border-top: 0; border-bottom: 0; padding: 0 15px 0 16px; background-color: #ffffff; padding-top: 5px; padding-bottom: 10px" bgcolor="#ffffff"><table class="text-paragra-pattern" cellspacing="0" cellpadding="0" border="0" width="100%" style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; font-family: Arial, sans-serif; font-size: 14px; line-height: 20px; mso-line-height-rule: exactly; mso-text-raise: 2px">
                  <tbody>
                    <tr>
                      <td class="text-paragraph-pattern-container mobile-resize-text " style="padding: 0px; border-collapse: collapse; padding: 0 0 10px 0"><span class="diffcontext">{{$data['Description']}}</span> <span class="diffremovedchars" style="background-color: #ffe7e7; text-decoration:line-through;"> </span> <span class="diffaddedchars" style="background-color:#ddfade;"> <br>
                        <br>
                        </span></td>
                    </tr>
                  </tbody>
                </table></td>
            </tr>
            
            <!-- there needs to be content in the cell for it to render in some clients -->
            <tr>
              <td class="email-content-rounded-bottom mobile-expand" style="padding: 0px; border-collapse: collapse; color: #ffffff; padding: 0 15px 0 16px; height: 5px; line-height: 5px; background-color: #ffffff; border-top: 0; border-left: 1px solid #cccccc; border-bottom: 1px solid #cccccc; border-right: 1px solid #cccccc; border-bottom-right-radius: 5px; border-bottom-left-radius: 5px; mso-line-height-rule: exactly" height="5" bgcolor="#ffffff">&nbsp;</td>
            </tr>
          </tbody>
        </table></td>
    </tr>
  </tbody>
   <tr><td></td></tr>
  <tr><td></td></tr>
</table>
</body>
</html>