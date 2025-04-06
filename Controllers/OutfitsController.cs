[ApiController]
[Route("api/outfits")]
public class OutfitsController : ControllerBase
{
    private readonly WearWiseDb _db;

    public OutfitsController()
    {
        _db = new WearWiseDb();
    }

    [HttpGet]
    public IActionResult GetAllOutfits()
    {
        try
        {
            var outfits = _db.GetOutfitsWithImages();
            return Ok(outfits);
        }
        catch (Exception ex)
        {
            return StatusCode(500, $"Error interno: {ex.Message}");
        }
    }

    [HttpGet("{id}")]
    public IActionResult GetOutfitById(int id)
    {
        try
        {
            var outfits = _db.GetOutfitsWithImages();
            var outfit = outfits.FirstOrDefault(o => o.Id == id);
            
            if (outfit == null)
                return NotFound();
                
            return Ok(outfit);
        }
        catch (Exception ex)
        {
            return StatusCode(500, $"Error interno: {ex.Message}");
        }
    }
}