@php
    $appName = config('app.name');
    $logo = config('mail.logo') ?: secure_asset('https://marthire.sovereignrefinery.com/assets/images/logo.png');

    $links = [];
    if ($support = config('mail.footer.support_url')) {
        $links[__('Support')] = Str::startsWith($support, 'http') ? $support : secure_url($support);
    }
    if ($privacy = config('mail.footer.privacy_url')) {
        $links[__('Privacy Policy')] = Str::startsWith($privacy, 'http') ? $privacy : secure_url($privacy);
    }
    if ($terms = config('mail.footer.terms_url')) {
        $links[__('Terms')] = Str::startsWith($terms, 'http') ? $terms : secure_url($terms);
    }
    if ($unsub = config('mail.footer.unsubscribe_url')) {
        $links[__('Unsubscribe')] = Str::startsWith($unsub, 'http') ? $unsub : secure_url($unsub);
    }
@endphp

<tr>
<td>
    <table class="footer" align="center" width="600" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
            <td class="footer-cell" align="center">

                {{-- Logo --}}
                <a href="#" target="_blank" rel="noopener">
                    <img src="https://marthire.sovereignrefinery.com/assets/images/logo.png" alt="Marthire" height="26"
                         style="border:0; display:inline-block; height:26px; width:auto;">
                </a>

                {{-- Social icons --}}
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" style="margin:18px auto;">
                    <tr>
                        <td style="padding:0 7px;">
                            <a href="https://www.facebook.com/MartHirerecruit" target="_blank" rel="noopener">
                                <img src="https://marthire.sovereignrefinery.com/assets/images/social-icons/facebook.png"
                                     alt="Facebook" width="22" height="22"
                                     style="display:block; width:22px; height:22px; border:0; font-size:11px; color:#022A5E;">
                            </a>
                        </td>
                        <td style="padding:0 7px;">
                            <a href="https://www.instagram.com/marthirerecruit" target="_blank" rel="noopener">
                                <img src="https://marthire.sovereignrefinery.com/assets/images/social-icons/instagram.png"
                                     alt="Instagram" width="22" height="22"
                                     style="display:block; width:22px; height:22px; border:0; font-size:11px; color:#022A5E;">
                            </a>
                        </td>
                        <td style="padding:0 7px;">
                            <a href="https://x.com/MartHirerecruit" target="_blank" rel="noopener">
                                <img src="https://marthire.sovereignrefinery.com/assets/images/social-icons/x-twitter.png"
                                     alt="X" width="22" height="22"
                                     style="display:block; width:22px; height:22px; border:0; font-size:11px; color:#022A5E;">
                            </a>
                        </td>
                        <td style="padding:0 7px;">
                            <a href="https://www.linkedin.com/company/marthire/" target="_blank" rel="noopener">
                                <img src="https://marthire.sovereignrefinery.com/assets/images/social-icons/linkedin.png"
                                     alt="LinkedIn" width="22" height="22"
                                     style="display:block; width:22px; height:22px; border:0; font-size:11px; color:#022A5E;">
                            </a>
                        </td>
                    </tr>
                </table>

                {{-- Text links --}}
                <!--p class="footer-links">
                    <a href="https://yourdomain.com/contact" target="_blank" rel="noopener">Support</a>
                    <span class="footer-sep">&bull;</span>
                    <a href="https://yourdomain.com/privacy-policy" target="_blank" rel="noopener">Privacy Policy</a>
                    <span class="footer-sep">&bull;</span>
                    <a href="https://yourdomain.com/terms" target="_blank" rel="noopener">Terms</a>
                </p-->

                <p class="footer-copy">
                    &copy; {{ date('Y') }} Marthire. All rights reserved.
                </p>
                <p class="footer-muted">
                    You are receiving this email because you have an account with Marthire.
                </p>

            </td>
        </tr>
    </table>
</td>
</tr>
  
