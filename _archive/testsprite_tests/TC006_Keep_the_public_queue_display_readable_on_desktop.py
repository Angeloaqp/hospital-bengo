import asyncio
import re
from playwright import async_api
from playwright.async_api import expect

async def run_test():
    pw = None
    browser = None
    context = None

    try:
        pw = await async_api.async_playwright().start()
        browser = await pw.chromium.launch(
            headless=True,
            args=[
                "--window-size=1280,720",
                "--disable-dev-shm-usage",
                "--ipc=host",
                "--single-process"
            ],
        )
        context = await browser.new_context()
        context.set_default_timeout(15000)
        page = await context.new_page()
        # -> Final action — this is where the agent failed
        # Error observed by agent: Navigation failed - site unavailable: http://localhost/hospital-bengo/public/painel.php
        await page.goto("http://localhost/hospital-bengo/public/painel.php")
        try:
            await page.wait_for_load_state("domcontentloaded", timeout=5000)
        except Exception:
            pass
        
        # --> Test failed (AST guard fallback)
        raise AssertionError("Test failed during agent run: " + "TEST FAILURE Character-encoding and spacing issues reduce the public panel's readability and therefore the panel is not fully usable without fixes. Observations: - The consult room label contains character-encoding artifacts (displayed as \"Consult\u251c\u2502rio 1\"). - Queue entries are rendered without proper spacing or with embedded icon text (e.g. \"I-001Aguardando Utente\"), making the list hard to rea...")
        await asyncio.sleep(5)
    finally:
        if context:
            await context.close()
        if browser:
            await browser.close()
        if pw:
            await pw.stop()

asyncio.run(run_test())
    