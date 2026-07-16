<!DOCTYPE html>
<html>

<?php include __DIR__ . '/mail-head.php'; ?>

<body 
    style="background-color: #f0edeb; margin: 0; padding: 25px 0; font-family: 'Roboto', Arial, sans-serif; 
        -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%;"
>

    <table 
        border="0" 
        cellpadding="0" 
        cellspacing="0" 
        width="100%" 
        style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 10px; 
            box-shadow: 0px 0px 20px -5px rgba(76, 126, 254, 1); overflow: hidden; border-collapse: collapse;"
    >

        <?php include __DIR__ . '/mail-header.php'; ?>

        <tr>
            <td 
                style="padding: 30px 25px 20px 25px;"
            >
                <p 
                    style="font-size: 22px; font-weight: 600; text-align: center; margin: 0 0 20px 0; 
                        color: #333333; font-family: 'Roboto', Arial, sans-serif;"
                >
                    Hello <?= htmlspecialchars($username ?? '') ?>
                </p>
                
                <p 
                    style="font-size: 18px; font-weight: 500; text-align: center; margin: 0 0 15px 0; 
                        line-height: 24px; color: #555555; font-family: 'Roboto', Arial, sans-serif;"
                >
                    Somebody made a request to set new password for your account on website 
                    <a 
                        href="<?= $website ?? '' ?>" 
                        style="text-decoration: underline; color: #2196f3; font-weight: 600;"
                    >
                        <?= $siteTitle ?? '' ?>
                    </a>
                </p>
                
                <p 
                    style="font-size: 18px; font-weight: 500; text-align: center; margin: 0 0 15px 0; 
                        line-height: 24px; color: #555555; font-family: 'Roboto', Arial, sans-serif;"
                >
                    If that wasn't you just ignore this message. 
                    Your password will remain unchanged and account secured.
                </p>
                
                <p 
                    style="font-size: 18px; font-weight: 500; text-align: center; margin: 0 0 20px 0; 
                        line-height: 24px; color: #555555; font-family: 'Roboto', Arial, sans-serif;"
                >
                    To set new password for your account click below:
                </p>
                
                <p 
                    style="font-size: 18px; font-weight: 600; text-align: center; font-style: italic; 
                        margin: 0 0 20px 0; line-height: 26px; font-family: 'Roboto', Arial, sans-serif;"
                    >
                    <a 
                        href="<?= $website ?? '' ?>/?user_id=<?= $userId ?? '' ?>&auth=<?= $auth ?? '' ?>" 
                        style="text-decoration: underline; color: #2196f3;"
                    >
                        click here
                    </a>
                </p>
            </td>
        </tr>

        <?php include __DIR__ . '/mail-footer.php'; ?>

    </table>

</body>
</html>