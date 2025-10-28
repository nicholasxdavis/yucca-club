<?php
require_once 'config.php';
$page_title = "Privacy Policy - Yucca Club";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <link rel="icon" type="image/png" href="ui/img/favicon.png">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Lora:wght@500;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root { --desert-sand: #F5F1E9; --yucca-yellow: #a8aa19; --lobo-gray: #63666A; --off-white: #FFFFFF; --darker-sand: #ede9df; }
        html[data-theme='dark'] { --desert-sand: #1a1a1a; --yucca-yellow: #b8ba20; --lobo-gray: #d1d1d1; --off-white: #252525; --darker-sand: #111111; }
        body { font-family: 'Lato', sans-serif; background: var(--desert-sand); color: var(--lobo-gray); line-height: 1.6; padding: 2rem 0; }
        .container { max-width: 800px; margin: 0 auto; padding: 0 1.5rem; }
        .back-link { display: inline-block; margin-bottom: 2rem; color: var(--yucca-yellow); text-decoration: none; font-weight: 700; }
        .back-link:hover { text-decoration: underline; }
        h1 { font-family: 'Lora', serif; font-size: 2.5rem; color: var(--lobo-gray); margin-bottom: 1rem; }
        .last-updated { color: #999; font-size: 0.9rem; margin-bottom: 2rem; }
        h2 { font-family: 'Lora', serif; font-size: 1.8rem; color: var(--lobo-gray); margin-top: 2.5rem; margin-bottom: 1rem; }
        h3 { font-family: 'Lato', serif; font-size: 1.3rem; color: var(--lobo-gray); margin-top: 1.5rem; margin-bottom: 0.75rem; }
        p { margin-bottom: 1.2rem; }
        ul, ol { margin-left: 2rem; margin-bottom: 1.2rem; }
        li { margin-bottom: 0.6rem; }
        .highlight-box { background: var(--off-white); padding: 1.5rem; border-radius: 8px; border-left: 4px solid var(--yucca-yellow); margin: 2rem 0; }
        .contact-info { margin-top: 2rem; padding-top: 2rem; border-top: 2px solid var(--darker-sand); }
        .contact-info a { color: var(--yucca-yellow); text-decoration: none; }
        .contact-info a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-link">← Back to Home</a>
        
        <h1>Privacy Policy</h1>
        <p class="last-updated">Last Updated: <?= date('F j, Y') ?></p>
        
        <div class="highlight-box">
            <p><strong>At Yucca Club</strong>, we respect your privacy and are committed to protecting your personal information. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you visit our website.</p>
        </div>

        <h2>1. Information We Collect</h2>
        
        <h3>Personal Information</h3>
        <p>We may collect personal information that you voluntarily provide to us when you:</p>
        <ul>
            <li>Register for an account on our website</li>
            <li>Subscribe to our newsletter</li>
            <li>Submit content, stories, or comments</li>
            <li>Contact us through our contact form</li>
            <li>Participate in surveys, contests, or promotions</li>
        </ul>
        <p>This information may include:</p>
        <ul>
            <li>Name</li>
            <li>Email address</li>
            <li>Password (hashed and securely stored)</li>
            <li>Content you submit (stories, comments, guides)</li>
            <li>Any other information you choose to provide</li>
        </ul>

        <h3>Automatically Collected Information</h3>
        <p>When you visit our website, we may automatically collect certain information about your device and how you interact with our site, including:</p>
        <ul>
            <li>IP address</li>
            <li>Browser type and version</li>
            <li>Operating system</li>
            <li>Pages viewed and time spent on pages</li>
            <li>Referring website addresses</li>
            <li>Date and time of your visit</li>
            <li>Location data (general geographic area, not precise location)</li>
        </ul>

        <h2>2. How We Use Your Information</h2>
        <p>We use the information we collect for the following purposes:</p>
        <ul>
            <li><strong>To Provide Our Services:</strong> Process your registration, manage your account, and deliver the content you've signed up for</li>
            <li><strong>To Communicate with You:</strong> Send you newsletters, respond to your inquiries, and provide customer support</li>
            <li><strong>To Improve Our Website:</strong> Analyze how visitors use our site to improve functionality and user experience</li>
            <li><strong>To Publish Content:</strong> Publish and feature stories, guides, and content you submit</li>
            <li><strong>To Ensure Security:</strong> Monitor for fraud, abuse, and unauthorized access</li>
            <li><strong>To Comply with Legal Obligations:</strong> Meet legal, regulatory, and compliance requirements</li>
            <li><strong>For Marketing:</strong> Send you promotional communications about relevant Las Cruces events, businesses, and community news (with your consent)</li>
        </ul>

        <h2>3. How We Share Your Information</h2>
        <p>We do not sell your personal information. We may share your information in the following circumstances:</p>
        <ul>
            <li><strong>Service Providers:</strong> With third-party service providers who help us operate our website (hosting, email delivery, analytics)</li>
            <li><strong>Legal Requirements:</strong> When required by law, court order, or government request</li>
            <li><strong>Business Transfers:</strong> In connection with a merger, acquisition, or sale of assets</li>
            <li><strong>With Your Consent:</strong> When you explicitly authorize us to share your information</li>
            <li><strong>Public Content:</strong> Stories, guides, and content you publish will be publicly visible on our website</li>
        </ul>

        <h2>4. Data Storage and Security</h2>
        <p>We implement appropriate technical and organizational measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction. This includes:</p>
        <ul>
            <li>Secure password hashing (bcrypt)</li>
            <li>Encrypted database connections</li>
            <li>Regular security updates and monitoring</li>
            <li>Access controls and authentication</li>
            <li>Secure hosting infrastructure</li>
        </ul>
        <p><strong>However, no method of transmission over the Internet or electronic storage is 100% secure.</strong> While we strive to protect your personal information, we cannot guarantee absolute security.</p>

        <h2>5. Cookies and Tracking Technologies</h2>
        <p>We use cookies and similar tracking technologies to:</p>
        <ul>
            <li>Remember your preferences and settings</li>
            <li>Analyze website traffic and usage patterns</li>
            <li>Provide personalized content and advertisements</li>
            <li>Improve website performance</li>
        </ul>
        <p>You can control cookies through your browser settings. However, disabling cookies may limit your ability to use certain features of our website.</p>

        <h2>6. Third-Party Services</h2>
        <p>Our website may contain links to third-party websites or integrate with third-party services, including:</p>
        <ul>
            <li><strong>Email Services:</strong> For newsletter delivery</li>
            <li><strong>Analytics:</strong> To understand how users interact with our site</li>
            <li><strong>Social Media:</strong> For content sharing and engagement</li>
            <li><strong>Payment Processors:</strong> If you make purchases through our site</li>
        </ul>
        <p>These third-party services have their own privacy policies. We encourage you to review their policies as we are not responsible for their practices.</p>

        <h2>7. Your Rights and Choices</h2>
        <p>You have the following rights regarding your personal information:</p>
        <ul>
            <li><strong>Access:</strong> Request a copy of the personal information we hold about you</li>
            <li><strong>Correction:</strong> Request correction of inaccurate or incomplete information</li>
            <li><strong>Deletion:</strong> Request deletion of your personal information (subject to legal requirements)</li>
            <li><strong>Opt-Out:</strong> Unsubscribe from marketing communications at any time</li>
            <li><strong>Account Management:</strong> Update or delete your account through your account settings</li>
            <li><strong>Data Portability:</strong> Request your data in a structured, commonly used format</li>
            <li><strong>Objection:</strong> Object to processing of your personal information for certain purposes</li>
        </ul>
        <p>To exercise any of these rights, please contact us using the information provided below.</p>

        <h2>8. Children's Privacy</h2>
        <p>Our website is not directed to children under the age of 13. We do not knowingly collect personal information from children under 13. If you believe we have inadvertently collected information from a child under 13, please contact us immediately, and we will take steps to delete that information.</p>

        <h2>9. Data Retention</h2>
        <p>We retain your personal information for as long as necessary to:</p>
        <ul>
            <li>Provide our services to you</li>
            <li>Comply with legal obligations</li>
            <li>Resolve disputes and enforce our agreements</li>
            <li>Maintain historical records of published content</li>
        </ul>
        <p>When you delete your account, we will delete or anonymize your personal information, except where we are required to retain it for legal purposes or where you have contributed public content that remains on the site.</p>

        <h2>10. International Data Transfers</h2>
        <p>If you are visiting our website from outside the United States, please be aware that your information may be transferred to, stored, and processed in the United States, where our servers are located. By using our website, you consent to the transfer of your information to the United States.</p>

        <h2>11. California Privacy Rights</h2>
        <p>If you are a California resident, you have additional rights under the California Consumer Privacy Act (CCPA):</p>
        <ul>
            <li>Right to know what personal information is collected</li>
            <li>Right to know if your personal information is sold or disclosed</li>
            <li>Right to opt-out of the sale of personal information</li>
            <li>Right to non-discrimination for exercising your privacy rights</li>
        </ul>
        <p><strong>Note:</strong> We do not sell personal information. If you are a California resident and wish to exercise your rights, please contact us using the information below.</p>

        <h2>12. Changes to This Privacy Policy</h2>
        <p>We may update this Privacy Policy from time to time to reflect changes in our practices or for other operational, legal, or regulatory reasons. We will notify you of any material changes by:</p>
        <ul>
            <li>Posting the new Privacy Policy on this page</li>
            <li>Updating the "Last Updated" date at the top of this policy</li>
            <li>Sending you an email notification (for significant changes)</li>
        </ul>
        <p>Your continued use of our website after any changes indicates your acceptance of the updated Privacy Policy.</p>

        <h2>13. How to Contact Us</h2>
        <div class="contact-info">
            <p>If you have questions, concerns, or requests regarding this Privacy Policy or our data practices, please contact us:</p>
            <p>
                <strong>Yucca Club</strong><br>
                Las Cruces, New Mexico<br>
                Email: <a href="mailto:contact@yuccaclub.com">contact@yuccaclub.com</a><br>
                Website: <a href="https://www.yuccaclub.com">www.yuccaclub.com</a>
            </p>
            <p>We will respond to your inquiry within a reasonable timeframe.</p>
        </div>

        <h2>14. Local Focus</h2>
        <p><strong>Yucca Club</strong> is dedicated to serving the Las Cruces and El Paso metro area community. We collect information relevant to local businesses, events, and community content. When you submit local stories, event information, or business reviews, this information may be used to enrich our content and better serve our local community.</p>

        <div class="highlight-box" style="margin-top: 3rem; text-align: center;">
            <p style="margin: 0;"><strong>Thank you for being part of the Yucca Club community!</strong></p>
        </div>
    </div>
</body>
</html>
