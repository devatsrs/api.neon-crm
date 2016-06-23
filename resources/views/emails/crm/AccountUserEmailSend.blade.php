<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width">
    <title>Commit</title>
</head>
<body>
<table align="center" style="vertical-align: top; border-collapse: collapse; padding-bottom: 0px; padding-top: 0px; padding-left: 0px; border-spacing: 0; padding-right: 0px; width: 95%; background-color: #f0f0f0">
    <tbody>
    <tr style="vertical-align: top; padding-bottom: 0px; text-align: center; padding-top: 0px; padding-left: 0px; padding-right: 0px">
        <td style="vertical-align: top; padding-bottom: 0px; text-align: center; padding-top: 0px; padding-left: 0px; padding-right: 0px">{{$data['Logo']}}</td>
    </tr>
    </tbody>
</table>
<table align="center" style="vertical-align: top; border-collapse: collapse; padding-bottom: 0px; padding-top: 0px; padding-left: 0px; border-spacing: 0; padding-right: 0px; width: 95%; background-color: #f0f0f0">
    <tbody>
    <tr style="vertical-align: top; padding-bottom: 0px; text-align: left; padding-top: 0px; padding-left: 0px; padding-right: 0px">
        <td width="5%"></td>
        <td width="90%" style="font-size: 14px; font-family: 'helvetica', 'arial', sans-serif; vertical-align: top; border-collapse: collapse; font-weight: normal; color: #4d4d4d; padding-bottom: 16px; text-align: center; padding-top: 16px; padding-left: 0px; margin: 0px; line-height: 19px; padding-right: 0px; -moz-hyphens: auto; -webkit-hyphens: auto; hyphens: auto">
            <div style="width: 90%; padding-bottom: 12px; padding-top: 12px; padding-left: 16px; margin: 0px auto; display: block; padding-right: 16px">
                <h6 style="font-size: 20px; font-family: 'helvetica', 'arial', sans-serif; word-break: normal; font-weight: normal; color: #4d4d4d; padding-bottom: 0px; text-align: left; padding-top: 0px; padding-left: 0px; margin: 0px 0px 12px; line-height: 1.3; padding-right: 0px">Update
                </h6>
                <table class=phenom style="vertical-align: top; border-collapse: collapse; padding-bottom: 0px; text-align: left; padding-top: 0px; padding-left: 0px; margin: 12px 0px; border-spacing: 0; padding-right: 0px" width="100%">
                    <tbody>
                    <tr style="vertical-align: top; padding-bottom: 0px; text-align: left; padding-top: 0px; padding-left: 0px; padding-right: 0px">
                        <td class=phenom-details style="font-size: 14px; font-family: 'helvetica', 'arial', sans-serif; vertical-align: top; border-collapse: collapse; font-weight: normal; color: #4d4d4d; padding-bottom: 0px; text-align: left; padding-top: 0px; padding-left: 0px; margin: 0px; line-height: 19px; padding-right: 0px; -moz-hyphens: auto; -webkit-hyphens: auto; hyphens: auto">
                            <div style="float: left;clear:left;">
                                <strong>{{$data['CreatedBy']}}</strong> Commented on {{$data['Task']}}
                                <p style="font-size: 14px; font-family: 'helvetica', 'arial', sans-serif; font-weight: normal; color: #4d4d4d; text-align: left; margin: 0px 0px 10px; line-height: 19px; padding: 10px;background-color:#ffffff;border-color: #d6cfcf;border-radius: 10px;border-style: solid;border-width: 1px 3px 3px 1px;margin: 5px;max-width: 95%;padding: 5px;">
                                    {{nl2br($data['Message'])}}
                                </p>
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </td>
        <td width="5%"></td>
    </tr>
    </tbody>
</table>
</body>
</html>

