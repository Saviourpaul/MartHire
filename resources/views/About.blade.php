<x-layout title="About Marthire" description="Meet Marthire: a hiring platform that brings employers and job seekers closer to their next connection.">
    <section class="nw-interior-hero nw-container">
        <p class="nw-eyebrow">About Marthire</p>
        <h1>Behind every opportunity,<br> a person.</h1>
        <p class="nw-copy">A new role can change a career. The right person can change a team. Marthire brings employers and job seekers closer to those possibilities.</p>
    </section>

    <div class="nw-container nw-about-visual">
        <img src="{{ asset('asset/marthire/conversation.png') }}" width="585" height="444" alt="A professional greeting someone during a laptop video call" loading="lazy">
        <div>
            <p class="nw-eyebrow">Built around connection</p>
            <h2 class="nw-heading">Good work starts<br> with people.</h2>
            <p class="nw-copy">Hiring is more than filling a vacancy. It is a chance to bring new ideas into a business and open a new chapter in someone's working life.</p>
            <p class="nw-copy">We are building Marthire to give both sides of that conversation a place to begin.</p>
        </div>
    </div>

    <section class="nw-section nw-container nw-mission" aria-labelledby="mission-heading">
        <p class="nw-eyebrow">Our mission</p>
        <div>
            <h2 class="nw-heading" id="mission-heading">Bring talent and<br> opportunity closer.</h2>
            <p class="nw-copy">Our focus is straightforward: help employers connect with people for their teams, and help job seekers find their next direction. Talent sourcing and job postings sit at the heart of that work.</p>
            <p class="nw-copy">We believe the experience should feel clear, considered, and human from the first connection.</p>
        </div>
    </section>

    <section class="nw-section nw-section--soft" aria-labelledby="values-heading">
        <div class="nw-container">
            <div class="nw-section-heading">
                <p class="nw-eyebrow">What guides us</p>
                <h2 class="nw-heading" id="values-heading">A little more human.<br> At every step.</h2>
            </div>
            <div class="nw-values">
                <article class="nw-value">
                    <x-icon name="users" />
                    <h3>People come first.</h3>
                    <p>There is a person behind every application and a team behind every role. Both deserve a thoughtful experience.</p>
                </article>
                <article class="nw-value">
                    <x-icon name="briefcase" />
                    <h3>Make the next step clear.</h3>
                    <p>Good connections begin with understanding. We value straightforward language and a clear sense of direction.</p>
                </article>
                <article class="nw-value">
                    <x-icon name="globe" />
                    <h3>Leave room for possibility.</h3>
                    <p>Skills, experience, and ambition take many forms. We believe opportunity should make space for different perspectives.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="nw-cta" aria-labelledby="about-cta-heading">
        <div class="nw-container nw-cta__inner">
            <div>
                <p class="nw-eyebrow">Your next chapter</p>
                <h2 id="about-cta-heading">Let's make<br> a connection.</h2>
            </div>
            <a class="nw-button nw-button--light" href="{{ route('register') }}">Get started <x-icon name="arrow-right" /></a>
        </div>
    </section>
</x-layout>